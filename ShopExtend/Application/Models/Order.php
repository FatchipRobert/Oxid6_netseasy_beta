<?php

namespace Es\NetsEasy\ShopExtend\Application\Models;

use Es\NetsEasy\Api\NetsLog;
use Es\NetsEasy\Core\CommonHelper;
use Es\NetsEasy\ShopExtend\Application\Models\BasketItems;
use Es\NetsEasy\ShopExtend\Application\Models\Payment;
use Es\NetsEasy\ShopExtend\Application\Models\Address;
use OxidEsales\EshopCommunity\Core\Registry;
use OxidEsales\EshopCommunity\Core\Request;

/**
 * Nets oxOrder class
 * @mixin Es\NetsEasy\ShopExtend\Application\Model\Order
 */
class Order
{

    const EMBEDDED = "EmbeddedCheckout";
    const HOSTED = "HostedPaymentPage";
    const ENDPOINT_TEST = 'https://test.api.dibspayment.eu/v1/payments/';
    const ENDPOINT_LIVE = 'https://api.dibspayment.eu/v1/payments/';
    const JS_ENDPOINT_TEST = "https://test.checkout.dibspayment.eu/v1/checkout.js";
    const JS_ENDPOINT_LIVE = "https://checkout.dibspayment.eu/v1/checkout.js";
    const RESPONSE_TYPE = "application/json";
    const MODULE_NAME = "nets_easy";

    protected $integrationType;
    public $_NetsLog = true;
    protected $oCommonHelper;
    protected $oOrder;
    protected $oxUtils;
    protected $oxOrder;
    protected $netsLog;
    protected $oxBasketItems;
    protected $oxPayment;
    protected $oxAddress;

    public function __construct($oOrder = null, $commonHelper = null, $oxUtils = null, $oxOrder = null, $oxBasketItems = null, $oxPayment = null, $oxAddress = null)
    {
        $this->_NetsLog = true;
        $this->netsLog = \oxNew(NetsLog::class);

        if (!$oOrder) {
            $this->oOrder = $this;
        } else {
            $this->oOrder = $oOrder;
        }
        // works only if StaticHelper is not autoloaded yet!
        if (!$commonHelper) {
            $this->oCommonHelper = \oxNew(CommonHelper::class);
        } else {
            $this->oCommonHelper = $commonHelper;
        }
        if (!$oxUtils) {
            $this->oxUtils = Registry::getUtils();
        } else {
            $this->oxUtils = $oxUtils;
        }
        if (!$oxOrder) {
            $this->oxOrder = \oxNew(\OxidEsales\Eshop\Application\Model\Order::class);
        } else {
            $this->oxOrder = $oxOrder;
        }

        if (!$oxBasketItems) {
            $this->oxBasketItems = \oxNew(BasketItems::class);
        } else {
            $this->oxBasketItems = $oxBasketItems;
        }
        if (!$oxPayment) {
            $this->oxPayment = \oxNew(Payment::class);
        } else {
            $this->oxPayment = $oxPayment;
        }
        if (!$oxAddress) {
            $this->oxAddress = \oxNew(Address::class);
        } else {
            $this->oxAddress = $oxAddress;
        }
    }

    /**
     * Function to create transaction and call nets payment Api
     * @param $oOrder
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    public function createNetsTransaction($oOrder)
    {
        $this->_NetsLog = true;
        Registry::getSession()->deleteVariable('nets_err_msg');
        NetsLog::log($this->_NetsLog, "NetsOrder createNetsTransaction");
        $items = [];
        $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $this->integrationType = self::HOSTED;
        $sUserID = Registry::getSession()->getVariable("usr");
        $oUser = \oxNew("oxuser", "core");
        $oUser->Load($sUserID);
        $sCountryId = $oUser->oxuser__oxcountryid->value;
        $mySession = Registry::getSession();
        $oBasket = $mySession->getBasket();
        $oID = $this->oOrder->updateOrdernr(Registry::getSession()
                        ->getVariable('sess_challenge'));
        $this->oOrder->logOrderID($oOrder, $oID);
        $oID = $this->oOrder->getOrderId();
        $daten = $this->oxAddress->setAddress($oUser, $sTranslation = '', $oBasket);
        $basketcontents = $oBasket->getContents();
        $this->oxBasketItems->getItemList($oBasket);
        /* gift wrap and greeting card amount to be added in total amount */
        $wrappingCostAmt = $oBasket->getCosts('oxwrapping');
        $wrapCost = $greetCardAmt = $shipCostAmt = $payCostAmt = 0;
        if ($wrappingCostAmt) {
            $wrapCost = $oBasket->isCalculationModeNetto() ? $wrappingCostAmt->getNettoPrice() : $wrappingCostAmt->getBruttoPrice();
            $wrapCost = round(round($wrapCost, 2) * 100);
        }
        $greetingCardAmt = $oBasket->getCosts('oxgiftcard');
        if ($greetingCardAmt) {
            $greetCardAmt = $oBasket->isCalculationModeNetto() ? $greetingCardAmt->getNettoPrice() : $greetingCardAmt->getBruttoPrice();
            $greetCardAmt = round(round($greetCardAmt, 2) * 100);
        }
        $this->oxBasketItems->getDiscountItem($wrapCost, $greetCardAmt);
        $sumAmt = 0;
        foreach ($basketcontents as $item) {
            $items[] = $itemArray = $this->oxBasketItems->getProductItem($item);
            $sumAmt += $itemArray['grossTotalAmount'];
        }
        $sumAmt = $sumAmt + $wrapCost + $greetCardAmt + $shipCostAmt + $payCostAmt;
        $daten['delivery_address'] = $this->oxAddress->getDeliveryAddress($oOrder, $oDB, $oUser);
        // create order to be passed to nets api
        $data = [
            'order' => [
                'items' => $items,
                'amount' => $sumAmt,
                'currency' => $oBasket->getBasketCurrency()->name,
                'reference' => $oID
            ]
        ];
        $data = $this->oxPayment->prepareDatastringParams($daten, $data, $paymentId = null);
        try {
            return $this->oxPayment->getPaymentResponse($data, $oBasket, $oID);
        } catch (Exception $e) {
            $this->oOrder->logCatchErrors($e);
            Registry::getUtils()->redirect(Registry::getConfig()
                            ->getSslShopUrl() . 'index.php?cl=netsorder');
        }
        return true;
    }

    /**
     * Function to log Order ID
     * @return null
     */
    public function logOrderID($oOrder, $oID)
    {
        $this->netsLog->log($this->_NetsLog, 'oID: ', $oOrder->oxorder__oxordernr->value);
        // if oID is empty, use session value
        if (empty($oID)) {
            $sGetChallenge = Registry::getSession()->getVariable('sess_challenge');
            $oID = $sGetChallenge;
            $this->netsLog->log($this->_NetsLog, "NetsOrder, get oID from Session: ", $oID);
        }
        $this->netsLog->log($this->_NetsLog, 'oID: ', $oID);
    }

    /**
     * Function to log catch errors
     * @return null
     */
    public function logCatchErrors($e)
    {
        $error_message = $e->getMessage();
        $this->netsLog->log($this->_NetsLog, "NetsOrder, api exception : ", $e->getMessage());
        $this->netsLog->log($this->_NetsLog, "NetsOrder, $error_message");
        if (empty($error_message)) {
            $error_message = 'Payment Api Parameter issue';
        }
        Registry::getSession()->setVariable('nets_err_msg', $error_message);
    }

    /**
     * Function to finalizing ordering process (validating, storing order into DB, executing payment, setting status 
     * @return null
     */
    public function processOrder($oUser)
    {
        $sess_id = Registry::getSession()->getVariable('sess_challenge');
        $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
        $sSQL_select = "SELECT oxorder_id FROM oxnets WHERE oxorder_id = ? LIMIT 1";
        $order_id = $oDB->getOne($sSQL_select, [
            $sess_id
        ]);
        if (!empty($order_id)) {
            $orderId = \OxidEsales\Eshop\Core\UtilsObject::getInstance()->generateUID();
            \OxidEsales\Eshop\Core\Registry::getSession()->setVariable("sess_challenge", $orderId);
        }
        // finalizing ordering process (validating, storing order into DB, executing payment, setting status ...)
        $oBasket = Registry::getSession()->getBasket();
        $sDeliveryAddress = $oUser->getEncodedDeliveryAddress();
        $_POST['sDeliveryAddressMD5'] = $sDeliveryAddress;
        $iSuccess = $this->oxOrder->finalizeOrder($oBasket, $oUser);
        $paymentId = Registry::getSession()->getVariable('payment_id');
        $orderNr = null;
        if (isset($this->oxOrder->oxorder__oxordernr->value)) {
            $orderNr = $this->oxOrder->oxorder__oxordernr->value;
            $this->netsLog->log($this->_NetsLog, " refupdate NetsOrder, order nr", $this->oxOrder->oxorder__oxordernr->value);
            Registry::getSession()->setVariable('orderNr', $orderNr);
        }
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $oDb->execute("UPDATE oxnets SET oxordernr = ?,  hash = ?, oxorder_id = ? WHERE transaction_id = ? ", [
            $orderNr,
                    Registry::getSession()
                    ->getVariable('sess_challenge'),
                    Registry::getSession()
                    ->getVariable('sess_challenge'),
            $paymentId
        ]);
        $api_return = $this->oCommonHelper->getCurlResponse($this->oCommonHelper->getApiUrl() . $paymentId, "GET");
        $response = json_decode($api_return, true);
        $this->netsLog->log($this->_NetsLog, " payment api status NetsOrder, response", $response);
        $refUpdate = [
            'reference' => $orderNr,
            'checkoutUrl' => $response['payment']['checkout']['url']
        ];
        $this->netsLog->log($this->_NetsLog, " payment api status NetsOrder, response checkout url", $response['payment']['checkout']['url']);
        $this->netsLog->log($this->_NetsLog, " refupdate NetsOrder, response", $refUpdate);
        $this->oCommonHelper->getCurlResponse($this->oCommonHelper->getUpdateRefUrl($paymentId), 'PUT', json_encode($refUpdate));
        $chargeResponse = $this->oCommonHelper->getCurlResponse($this->oCommonHelper->getApiUrl() . $paymentId, 'GET');
        $api_ret = json_decode($chargeResponse, true);
        if (isset($api_ret)) {
            foreach ($api_ret['payment']['charges'] as $ky => $val) {
                foreach ($val['orderItems'] as $key => $value) {
                    if (isset($val['chargeId'])) {
                        $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
                        $charge_query = "INSERT INTO `oxnets` (`transaction_id`, `charge_id`,  `product_ref`, `charge_qty`, `charge_left_qty`) " . "values ('" . $paymentId . "', '" . $val['chargeId'] . "', '" . $value['reference'] . "', '" . $value['quantity'] . "', '" . $value['quantity'] . "')";
                        $oDB->Execute($charge_query);
                    }
                }
            }
        }
        return true;
    }

    /**
     * Function to update order no in oxnets table
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     * @return $oOrderrnr
     */
    public function updateOrdernr($hash)
    {
        $oID = $this->oOrder->getOrderId();
        $this->oxOrder->load($oID);
        $oOrdernr = $this->oxOrder->oxorder__oxordernr->value;
        $this->netsLog->log($this->_NetsLog, "NetsOrder, updateOrdernr: " . $oOrdernr . " for hash " . $hash);
        if (is_numeric($oOrdernr) && !empty($hash)) {
            $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
            $oDb->execute("UPDATE oxnets SET oxordernr = ? WHERE hash = ?", [
                $oOrdernr,
                $hash
            ]);
            $this->netsLog->log($this->_NetsLog, "NetsOrder, in if updateOrdernr: " . $oOrdernr . " for hash " . $hash);
        }
        return $oOrdernr;
    }

    /**
     * Function to get current order from basket
     * @return array
     */
    public function getOrderId()
    {
        $mySession = Registry::getSession();
        $oBasket = $mySession->getBasket();
        return $oBasket->getOrderId();
    }

    /**
     * Function to check if it embedded checkout
     * @return bool
     */
    public function isEmbedded()
    {
        $mode = Registry::getConfig()->getConfigParam('nets_checkout_mode');
        $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
        $sSQL_select = "SELECT OXACTIVE FROM oxpayments WHERE oxid = ? LIMIT 1";
        $payMethod = $oDB->getOne($sSQL_select, [
            self::MODULE_NAME
        ]);
        if ($mode == "embedded" && $payMethod == 1) {
            return true;
        }
        return false;
    }

}
