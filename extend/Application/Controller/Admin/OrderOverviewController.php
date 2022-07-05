<?php

namespace Es\NetsEasy\extend\Application\Controller\Admin;

use Es\NetsEasy\Api\NetsLog;
use Es\NetsEasy\Core\CommonHelper;
use Es\NetsEasy\extend\Application\Models\OrderOverview;

/**
 * Class controls Nets Order Overview - In use for admin order list customization
 * Cancel, Capture, Refund and Partial nets payments
 */
class OrderOverviewController extends OrderOverviewController_parent
{

    const ENDPOINT_TEST = 'https://test.api.dibspayment.eu/v1/payments/';
    const ENDPOINT_LIVE = 'https://api.dibspayment.eu/v1/payments/';
    const ENDPOINT_TEST_CHARGES = 'https://test.api.dibspayment.eu/v1/charges/';
    const ENDPOINT_LIVE_CHARGES = 'https://api.dibspayment.eu/v1/charges/';
    const RESPONSE_TYPE = "application/json";

    protected $_NetsLog;
    protected $oOrderOverview;
    protected $oCommonHelper = false;
    protected $oxUtils;
    protected $oOrderOverviewController;

    public function __construct($oOrderOverviewController = null, $oOrderOverview = null, $commonHelper = null, $oxUtils = null)
    {
        $this->_NetsLog = $this->getConfig()->getConfigParam('nets_blDebug_log');
        NetsLog::log($this->_NetsLog, "NetsOrderOverview, constructor");

        if (!$oOrderOverviewController) {
            $this->oOrderOverviewController = $this;
        } else {
            $this->oOrderOverviewController = $oOrderOverviewController;
        }
        if (!$oOrderOverview) {
            $this->oOrderOverview = \oxNew(OrderOverview::class);
        } else {
            $this->oOrderOverview = $oOrderOverview;
        }
        if (!$commonHelper) {
            $this->oCommonHelper = \oxNew(CommonHelper::class);
        } else {
            $this->oCommonHelper = $commonHelper;
        }
        if (!$oxUtils) {
            $this->oxUtils = \oxRegistry::getUtils();
        } else {
            $this->oxUtils = $oxUtils;
        }
    }

    /**
     * Function to check the nets payment status and display in admin order list backend page
     * @return array
     */
    public function isEasy($oxoder_id)
    {
        $allStatusReturn = false;
        $payMethod = $this->oOrderOverviewController->getPaymentMethod($oxoder_id);
        if ($payMethod == 'nets_easy') {
            $allStatus = $this->oOrderOverview->getEasyStatus($oxoder_id);
            $allStatusReturn = array(
                'payStatus' => $allStatus['payStatus'],
                'langStatus' => $allStatus['langStatus']
            );
        }
        return $allStatusReturn;
    }

    /**
     * Function to get pay language status
     * @return array
     */
    public function getPayLangStatus($response, $oxoder_id)
    {
        return $this->oOrderOverview->getPaymentStatus($response, $oxoder_id);
    }

    /*
     * Function to capture nets transaction - calls Charge API
     * redirects to admin overview listing page
     */

    public function getOrderCharged()
    {
        $stoken = \oxRegistry::getConfig()->getRequestParameter('stoken');
        $admin_sid = \oxRegistry::getConfig()->getRequestParameter('force_admin_sid');
        $this->oOrderOverview->getOrderCharged();
        return $this->oxUtils->redirect($this->getConfig()
                                ->getSslShopUrl() . 'admin/index.php?cl=admin_order&force_admin_sid' . $admin_sid . '&stoken=' . $stoken);
    }

    /*
     * Function to capture nets transaction - calls Refund API
     * redirects to admin overview listing page
     */

    public function getOrderRefund()
    {
        $stoken = \oxRegistry::getConfig()->getRequestParameter('stoken');
        $admin_sid = \oxRegistry::getConfig()->getRequestParameter('force_admin_sid');
        $this->oOrderOverview->getOrderRefund();
        return $this->oxUtils->redirect($this->getConfig()
                                ->getSslShopUrl() . 'admin/index.php?cl=admin_order&force_admin_sid' . $admin_sid . '&stoken=' . $stoken);
    }

    /*
     * Function to capture nets transaction - calls Cancel API
     * redirects to admin overview listing page
     */

    public function getOrderCancel()
    {
        $stoken = \oxRegistry::getConfig()->getRequestParameter('stoken');
        $admin_sid = \oxRegistry::getConfig()->getRequestParameter('force_admin_sid');
        $oxorder = \oxRegistry::getConfig()->getRequestParameter('oxorderid');
        $orderno = \oxRegistry::getConfig()->getRequestParameter('orderno');
        $data = $this->oOrderOverviewController->getOrderItems($oxorder);
        $payment_id = $this->oCommonHelper->getPaymentId($oxorder);
        // call cancel api here
        $cancelUrl = $this->oCommonHelper->getVoidPaymentUrl($payment_id);
        $body = [
            'amount' => $data['totalAmt'],
            'orderItems' => $data['items']
        ];
        $api_return = $this->oCommonHelper->getCurlResponse($cancelUrl, 'POST', json_encode($body));
        $response = json_decode($api_return, true);
        return $this->oxUtils->redirect($this->getConfig()
                                ->getSslShopUrl() . 'admin/index.php?cl=admin_order&force_admin_sid' . $admin_sid . '&stoken=' . $stoken);
    }

    /*
     * Function to get order items to pass capture, refund, cancel api
     * @param $oxorder oxid order id alphanumeric
     * @return array order items and amount
     */

    public function getOrderItems($oxorder, $blExcludeCanceled = true)
    {
        return $this->oOrderOverview->getOrderItems($oxorder, $blExcludeCanceled = true);
    }

    /*
     * Function to get list of partial charge/refund and reserved items list
     * @param oxorder id
     * @return array of reserved, partial charged,partial refunded items
     */

    public function checkPartialItems($oxid)
    {
        $prodItems = $this->oOrderOverviewController->getOrderItems($oxid);
        $products = [];
        $chargedItems = [];
        $refundedItems = [];
        $itemsList = [];
        foreach ($prodItems['items'] as $items) {
            $products[$items['reference']] = array(
                'name' => $items['name'],
                'quantity' => $items['quantity'],
                'price' => $items['oxbprice']
            );
        }
        $api_return = $this->oCommonHelper->getCurlResponse($this->oCommonHelper->getApiUrl() . $this->oCommonHelper->getPaymentId($oxid), 'GET');
        $response = json_decode($api_return, true);

        if (!empty($response['payment']['charges'])) {
            $chargedItems = $this->oOrderOverviewController->getChargedItems($response);
        }
        if (!empty($response['payment']['refunds'])) {
            $refundedItems = $this->oOrderOverviewController->getRefundedItems($response);
        }
        // get list of partial charged items and check with quantity and send list for charge rest of items
        foreach ($products as $key => $prod) {
            if (array_key_exists($key, $chargedItems)) {
                $qty = $prod['quantity'] - $chargedItems[$key]['quantity'];
            } else {
                $qty = $prod['quantity'];
            }
            if (array_key_exists($key, $chargedItems) && array_key_exists($key, $refundedItems)) {
                $qty = $chargedItems[$key]['quantity'] - $refundedItems[$key]['quantity'];
                if ($qty > 0)
                    $chargedItems[$key]['quantity'] = $qty;
            }
            if ($qty > 0) {
                $itemsList[] = array(
                    'name' => $prod['name'],
                    'reference' => $key,
                    'quantity' => $qty,
                    'price' => number_format((float) ($prod['price']), 2, '.', '')
                );
            }
            if (array_key_exists($key, $chargedItems) && array_key_exists($key, $refundedItems)) {
                if ($prod['quantity'] == $chargedItems[$key]['quantity'] && $chargedItems[$key]['quantity'] == $refundedItems[$key]['quantity']) {
                    unset($chargedItems[$key]);
                }
            }
            if ($chargedItems[$key]['quantity'] > $prod['quantity']) {
                $chargedItems[$key]['quantity'] = $prod['quantity'];
            }
        }
        $lists = $this->oOrderOverviewController->getLists($response, $itemsList, $chargedItems, $refundedItems);
        // pass reserved, charged, refunded items list to frontend
        return $lists;
    }

    /*
     * Get List of items to pass to frontend for charged, refunded items
     * @return array
     */

    public function getLists($response, $itemsList, $chargedItems, $refundedItems)
    {
        $reserved = $response['payment']['summary']['reservedAmount'];
        $charged = $response['payment']['summary']['chargedAmount'];
        if ($reserved != $charged) {
            if (count($itemsList) > 0) {
                $lists['reservedItems'] = $itemsList;
            }
        } else {
            if (count($chargedItems) > 0) {
                $lists['chargedItems'] = $chargedItems;
            }
        }
        $lists['chargedItemsOnly'] = $chargedItems;
        if (count($refundedItems) > 0) {
            $lists['refundedItems'] = $refundedItems;
        }
        return $lists;
    }

    /*
     * Fetch partial amount
     * @return int
     */

    public function getPartial($oxoder_id)
    {
        $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
        $sSQL_select = "SELECT partial_amount FROM oxnets WHERE oxorder_id = ? LIMIT 1";
        $partial_amount = $oDB->getOne($sSQL_select, [
            $oxoder_id
        ]);
        return $partial_amount;
    }

    /*
     * Function to enable debug mode
     * @return bool
     */

    public function debugMode()
    {
        $debug = $this->getConfig()->getConfigParam('nets_blDebug_log');
        return $debug;
    }

    /*
     * Function to get response
     * @return array
     */

    public function getResponse($oxoder_id)
    {
        $api_return = $this->oCommonHelper->getCurlResponse($this->oCommonHelper->getApiUrl() . $this->oCommonHelper->getPaymentId($oxoder_id), 'GET');
        $response = json_decode($api_return, true);
        $result = json_encode($response, JSON_PRETTY_PRINT);
        return $result;
    }

    /*
     * Function to fetch payment method type from databse table oxorder
     * @param $oxorder_id
     * @return payment method
     */

    public function getPaymentMethod($oxoder_id)
    {
        return $this->oOrderOverview->getPaymentMethod($oxoder_id);
    }

    /*
     * Function to fetch payment api url
     * @return payment api url
     */

    public function getApiUrl()
    {
        if ($this->getConfig()->getConfigParam('nets_blMode') == 0) {
            return self::ENDPOINT_TEST;
        } else {
            return self::ENDPOINT_LIVE;
        }
    }

    /*
     * Function to get charged items list
     * @return array
     */

    public function getChargedItems($response)
    {
        $qty = 0;
        $price = 0;
        $chargedItems = [];
        foreach ($response['payment']['charges'] as $key => $values) {
            for ($i = 0; $i < count($values['orderItems']); $i ++) {
                if (array_key_exists($values['orderItems'][$i]['reference'], $chargedItems)) {
                    $qty = $chargedItems[$values['orderItems'][$i]['reference']]['quantity'] + $values['orderItems'][$i]['quantity'];
                    $price = $chargedItems[$values['orderItems'][$i]['reference']]['price'] + number_format((float) ($values['orderItems'][$i]['grossTotalAmount'] / 100), 2, '.', '');
                    $priceGross = $price / $qty;
                    $chargedItems[$values['orderItems'][$i]['reference']] = array(
                        'name' => $values['orderItems'][$i]['name'],
                        'quantity' => $qty,
                        'price' => $priceGross
                    );
                } else {
                    $priceOne = $values['orderItems'][$i]['grossTotalAmount'] / $values['orderItems'][$i]['quantity'];
                    $chargedItems[$values['orderItems'][$i]['reference']] = array(
                        'name' => $values['orderItems'][$i]['name'],
                        'quantity' => $values['orderItems'][$i]['quantity'],
                        'price' => number_format((float) ($priceOne / 100), 2, '.', '')
                    );
                }
            }
        }
        return $chargedItems;
    }

    /*
     * Function to get refund items list
     * @return array
     */

    public function getRefundedItems($response)
    {
        $qty = 0;
        $price = 0;
        $refundedItems = [];
        foreach ($response['payment']['refunds'] as $key => $values) {
            for ($i = 0; $i < count($values['orderItems']); $i ++) {
                if (array_key_exists($values['orderItems'][$i]['reference'], $refundedItems)) {
                    $qty = $refundedItems[$values['orderItems'][$i]['reference']]['quantity'] + $values['orderItems'][$i]['quantity'];
                    $price = $values['orderItems'][$i]['grossTotalAmount'] * $qty;
                    $refundedItems[$values['orderItems'][$i]['reference']] = array(
                        'name' => $values['orderItems'][$i]['name'],
                        'quantity' => $qty,
                        'price' => number_format((float) ($price / 100), 2, '.', '')
                    );
                } else {
                    $refundedItems[$values['orderItems'][$i]['reference']] = array(
                        'name' => $values['orderItems'][$i]['name'],
                        'quantity' => $values['orderItems'][$i]['quantity'],
                        'price' => number_format((float) ($values['orderItems'][$i]['grossTotalAmount'] / 100), 2, '.', '')
                    );
                }
            }
        }
        return $refundedItems;
    }

    /*
     * Function to fetch payment id from database
     * @return payment id
     */

    public function getPaymentId($oxoder_id)
    {
        return $this->oCommonHelper->getPaymentId($oxoder_id);
    }

}
