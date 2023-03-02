<?php

namespace Es\NetsEasy\extend\Application\Controller\Admin;

use Es\NetsEasy\Application\Model\Api\Payment\PaymentCharge;
use Es\NetsEasy\Application\Model\Api\Payment\PaymentRetrieve;
use Es\NetsEasy\Application\Model\ResourceModel\NetsTransactions;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Application\Model\Order;
use Es\NetsEasy\Application\Model\PaymentStatus;

/**
 * Class controls Nets Order Overview - In use for admin order list customization
 * Cancel, Capture, Refund and Partial nets payments
 */
class OrderOverview extends OrderOverview_parent
{
    /**
     * Returns current order
     *
     * @return Order|false
     */
    protected function netsGetOrder()
    {
        $oOrder = oxNew(Order::class);
        if ($oOrder->load($this->getEditObjectId())) {
            return $oOrder;
        }
        return false;
    }

    /**
     * Function to check the nets payment status and display in admin order list backend page
     *
     * @return array
     */
    public function netsGetStatus()
    {
        $aReturn = false;
        $oOrder = $this->netsGetOrder();
        if ($oOrder && $oOrder->netsIsNetsPaymentUsed()) {
            $oPaymentStatus = oxNew(PaymentStatus::class);
            $allStatus = $oPaymentStatus->checkEasyStatus($oOrder);
            $aReturn = [
                'payStatus' => $allStatus['payStatus'],
                'langStatus' => $allStatus['langStatus']
            ];
        }
        return $aReturn;
    }

    /**
     * Function to capture nets transaction for full order - calls Charge API
     *
     * @return void
     */
    public function netsCaptureOrder()
    {
        $oOrder = $this->netsGetOrder();
        if ($oOrder) {
            $oOrder->netsCaptureOrder();
        }
    }

    /**
     * Function to capture nets transaction for a single reference - calls Charge API
     *
     * @return void
     */
    public function netsPartialCaptureOrder()
    {
        $sCaptureReference = Registry::getRequest()->getRequestEscapedParameter('reference');
        $iCaptureQuantity = Registry::getRequest()->getRequestEscapedParameter('charge');
        $oOrder = $this->netsGetOrder();
        if ($oOrder && !empty($sCaptureReference) && !empty($iCaptureQuantity)) {
            $oOrder->netsCaptureByReference($sCaptureReference, $iCaptureQuantity);
        }
    }

    /**
     * Function to capture nets transaction - calls Refund API
     *
     * @return void
     */
    public function netsRefundOrder()
    {
        $oOrder = $this->netsGetOrder();
        if ($oOrder) {
            $oOrder->netsRefundOrder();
        }
    }

    /**
     * Function to capture nets transaction - calls Refund API
     *
     * @return void
     */
    public function netsPartialRefundOrder()
    {
        $sRefundReference = Registry::getRequest()->getRequestEscapedParameter('reference');
        $iRefundQuantity = Registry::getRequest()->getRequestEscapedParameter('refund');
        $oOrder = $this->netsGetOrder();
        if ($oOrder) {
            $oOrder->netsRefundByReference($sRefundReference, $iRefundQuantity);
        }
    }

    /**
     * Function to capture nets transaction - calls Cancel API
     * redirects to admin overview listing page
     *
     * @return void
     */
    public function netsCancelOrder()
    {
        $oOrder = $this->netsGetOrder();
        if (!$oOrder) {
            return;
        }

        $oOrder->netsCancelOrder();
    }

    /**
     * Function to get list of partial charge/refund and reserved items list
     *
     * @return array
     */
    public function netsGetPartialItemList()
    {
        $oOrder = $this->netsGetOrder();

        $oPaymentCharge = oxNew(PaymentCharge::class);
        $aOrderItems = $oPaymentCharge->getOrderItems($oOrder, false);

        $aProducts = [];
        $aChargedItems = [];
        $aRefundedItems = [];
        $aItemList = [];
        foreach ($aOrderItems as $items) {
            $aProducts[$items['reference']] = [
                'name' => $items['name'],
                'quantity' => $items['quantity'],
                'price' => $items['unitPriceBrut']
            ];
        }

        $oPaymentInfo = oxNew(PaymentRetrieve::class);
        $aResponse = $oPaymentInfo->sendRequest($oOrder->oxorder__oxtransid->value);

        $A2A = $aResponse['payment']['paymentDetails']['paymentType'] == 'A2A' ? TRUE : FALSE;
        if ($A2A) {
            if (isset($aResponse['payment']['summary']['chargedAmount'])) {
                $aResponse['payment']['summary']['reservedAmount'] = $aResponse['payment']['summary']['chargedAmount'];
            }
        }
        if (!empty($aResponse['payment']['charges'])) {
            $aChargedItems = $this->netsGetChargedItems($aResponse);
        }
        if (!empty($aResponse['payment']['refunds'])) {
            $aRefundedItems = $this->netsGetRefundedItems($aResponse);
        }
        // get list of partial charged items and check with quantity and send list for charge rest of items
        foreach ($aProducts as $key => $prod) {
            if (array_key_exists($key, $aChargedItems)) {
                $qty = $prod['quantity'] - $aChargedItems[$key]['quantity'];
            } else {
                $qty = $prod['quantity'];
            }
            if (array_key_exists($key, $aChargedItems) && array_key_exists($key, $aRefundedItems)) {
                $qty = $aChargedItems[$key]['quantity'] - $aRefundedItems[$key]['quantity'];
                if ($qty > 0)
                    $aChargedItems[$key]['quantity'] = $qty;
            }
            if ($qty > 0) {
                $aItemList[] = [
                    'name' => $prod['name'],
                    'reference' => $key,
                    'quantity' => $qty,
                    'price' => number_format((float) ($prod['price']), 2, '.', '')
                ];
            }
            if (array_key_exists($key, $aChargedItems) && array_key_exists($key, $aRefundedItems)) {
                if ($prod['quantity'] == $aChargedItems[$key]['quantity'] && $aChargedItems[$key]['quantity'] == $aRefundedItems[$key]['quantity']) {
                    unset($aChargedItems[$key]);
                }
            }
            if ($aChargedItems[$key]['quantity'] > $prod['quantity']) {
                $aChargedItems[$key]['quantity'] = $prod['quantity'];
            }
        }
        $aLists = $this->netsGetLists($aResponse, $aItemList, $aChargedItems, $aRefundedItems);
        // pass reserved, charged, refunded items list to frontend
        return $aLists;
    }

    /**
     * Get List of items to pass to frontend for charged, refunded items
     *
     * @param  array $response
     * @param  array $itemsList
     * @param  array $chargedItems
     * @param  array $refundedItems
     * @return array
     */
    public function netsGetLists($response, $itemsList, $chargedItems, $refundedItems)
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

    /**
     * Function to enable debug mode
     *
     * @return bool
     */
    public function netsIsDebugModeActive()
    {
        return Registry::getConfig()->getConfigParam('nets_blDebug_log');
    }

    /**
     * Function to get response
     *
     * @return string
     */
    public function netsGetTransactionInfo()
    {
        $oOrder = $this->netsGetOrder();

        $oPaymentInfo = oxNew(PaymentRetrieve::class);
        $aResponse = $oPaymentInfo->sendRequest($oOrder->oxorder__oxtransid->value);

        $result = json_encode($aResponse, JSON_PRETTY_PRINT);
        return $result;
    }

    /**
     * Function to get charged items list
     *
     * @param  array $response
     * @return array
     */
    public function netsGetChargedItems($response)
    {
        $qty = 0;
        $price = 0;
        $chargedItems = [];
        foreach ($response['payment']['charges'] as $key => $values) {
            for ($i = 0; $i < count($values['orderItems']); $i ++) {
                if (array_key_exists($values['orderItems'][$i]['reference'], $chargedItems)) {
                    $aChargedItem = $chargedItems[$values['orderItems'][$i]['reference']];
                    $qty = $aChargedItem['quantity'] + $values['orderItems'][$i]['quantity'];
                    $price = ($aChargedItem['price'] * $aChargedItem['quantity']) + (float)($values['orderItems'][$i]['grossTotalAmount'] / 100);
                    $priceGross = $price / $qty;
                    $chargedItems[$values['orderItems'][$i]['reference']] = [
                        'name' => $values['orderItems'][$i]['name'],
                        'quantity' => $qty,
                        'price' => $priceGross
                    ];
                } else {
                    $priceOne = $values['orderItems'][$i]['grossTotalAmount'] / $values['orderItems'][$i]['quantity'];
                    $chargedItems[$values['orderItems'][$i]['reference']] = [
                        'name' => $values['orderItems'][$i]['name'],
                        'quantity' => $values['orderItems'][$i]['quantity'],
                        'price' => number_format((float) ($priceOne / 100), 2, '.', '')
                    ];
                }
            }
        }
        return $chargedItems;
    }

    /**
     * Function to get refund items list
     *
     * @param  array $response
     * @return array
     */
    public function netsGetRefundedItems($response)
    {
        $qty = 0;
        $price = 0;
        $refundedItems = [];
        foreach ($response['payment']['refunds'] as $key => $values) {
            for ($i = 0; $i < count($values['orderItems']); $i ++) {
                if (array_key_exists($values['orderItems'][$i]['reference'], $refundedItems)) {
                    $qty = $refundedItems[$values['orderItems'][$i]['reference']]['quantity'] + $values['orderItems'][$i]['quantity'];
                    $price = $values['orderItems'][$i]['grossTotalAmount'] * $qty;
                    $refundedItems[$values['orderItems'][$i]['reference']] = [
                        'name' => $values['orderItems'][$i]['name'],
                        'quantity' => $qty,
                        'price' => number_format((float) ($price / 100), 2, '.', '')
                    ];
                } else {
                    $refundedItems[$values['orderItems'][$i]['reference']] = [
                        'name' => $values['orderItems'][$i]['name'],
                        'quantity' => $values['orderItems'][$i]['quantity'],
                        'price' => number_format((float) ($values['orderItems'][$i]['grossTotalAmount'] / 100), 2, '.', '')
                    ];
                }
            }
        }
        return $refundedItems;
    }

    /**
     * Function to fetch payment id from database
     *
     * @return string
     */
    public function netsGetTransactionId()
    {
        return NetsTransactions::getTransactionIdByOrderId($this->netsGetOrder()->getId());
    }

}
