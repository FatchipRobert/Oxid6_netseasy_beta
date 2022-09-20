<?php

namespace Es\NetsEasy\ShopExtend\Application\Models;

use Es\NetsEasy\Core\CommonHelper;
use Es\NetsEasy\ShopExtend\Application\Models\BasketItems;
use Es\NetsEasy\ShopExtend\Application\Models\Payment;
use Es\NetsEasy\ShopExtend\Application\Models\Address;
use OxidEsales\EshopCommunity\Core\Registry;
use OxidEsales\EshopCommunity\Core\Request;
use \OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use \OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Core\Config;
use OxidEsales\Eshop\Application\Model\Order as OxOrder;
use Es\NetsEasy\Core\DebugHandler;

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
    protected $oCommonHelper;
    protected $oOrder;
    protected $oxUtils;
    protected $oxOrder;
    protected $oDebugHandler;
    protected $oxBasketItems;
    protected $oxPayment;
    protected $oxAddress;
    protected $queryBuilder;
    protected $oxConfig;
    protected $oxSession;

    /**
     * Constructor
     * @param  object $oOrder The Order model injected object
     * @param  object $commonHelper The service file injected as object
     * @param  object $oxUtils The OXID Utils injected object
     * @param  object $oxOrder The OXID Order Model injected object
     * @param  object $oxBasketItems The BasketItems Model injected object
     * @param  object $oxPayment The Payment Model injected object
     * @param  object $oxAddress The Address Model injected object
     * @return Null
     */
    public function __construct($oOrder = null, CommonHelper $commonHelper, $oxUtils = null, OxOrder $oxOrder, BasketItems $oxBasketItems, Payment $oxPayment, Address $oxAddress)
    {
        $this->oDebugHandler = \oxNew(DebugHandler::class);
        $this->oxConfig = \oxNew(Config::class);
        $this->oxSession = \oxNew(\OxidEsales\EshopCommunity\Core\Session::class);
        $this->queryBuilder = ContainerFactory::getInstance()
                ->getContainer()
                ->get(QueryBuilderFactoryInterface::class);

        if (!$oOrder) {
            $this->oOrder = $this;
        } else {
            $this->oOrder = $oOrder;
        }
        // works only if StaticHelper is not autoloaded yet!
        $this->oCommonHelper = $commonHelper;
        if (!$oxUtils) {
            $this->oxUtils = Registry::getUtils();
        } else {
            $this->oxUtils = $oxUtils;
        }
        if (!$oxUtils) {
            $this->oxUtils = Registry::getUtils();
        } else {
            $this->oxUtils = $oxUtils;
        }
        $this->oxOrder = $oxOrder;
        $this->oxBasketItems = $oxBasketItems;
        $this->oxPayment = $oxPayment;
        $this->oxAddress = $oxAddress;
    }

    /**
     * Function to create transaction and call nets payment Api
     * @param object The OXID Order object
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @return bool
     */
    public function createNetsTransaction($oxOrder)
    {
        $this->oxSession->deleteVariable('nets_err_msg');
        $this->oDebugHandler->log("NetsOrder createNetsTransaction");
        $items = [];
        $this->integrationType = self::HOSTED;
        $sUserID = $this->oxSession->getVariable("usr");
        $oUser = \oxNew("oxuser", "core");
        $oUser->Load($sUserID);
        $sCountryId = $oUser->oxuser__oxcountryid->value;
        $mySession = $this->oxSession;
        $oBasket = $mySession->getBasket();
        if (isset($oxOrder->oxorder__oxordernr->value)) {
            $orderNr = $oxOrder->oxorder__oxordernr->value;
            $this->oxSession->setVariable('orderNr', $orderNr);
            $this->oxSession->setVariable('sess_challenge', $oxOrder->oxorder__oxid->value);
        }
        $oID = $this->oxSession->getVariable('sess_challenge');

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
        $daten['delivery_address'] = $this->oxAddress->getDeliveryAddress($oxOrder, $oDB = null, $oUser);
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
            Registry::getUtils()->redirect($this->oxConfig
                            ->getSslShopUrl() . 'index.php?cl=netsorder');
        }
        return true;
    }

    /**
     * Function to log catch errors
     * @param $e The OXID exception object
     * @return null
     */
    public function logCatchErrors($e)
    {
        $error_message = $e->getMessage();
        $this->oDebugHandler->log("NetsOrder, api exception : " . $e->getMessage());
        $this->oDebugHandler->log("NetsOrder $error_message");
        if (empty($error_message)) {
            $error_message = 'Payment Api Parameter issue';
        }
        $this->oxSession->setVariable('nets_err_msg', $error_message);
    }

    /**
     * Function to finalizing ordering process (validating, storing order into DB, executing payment, setting status 
     * @param object The OXID User object
     * @return bool
     */
    public function processOrder($oUser)
    {
        $sess_id = $this->oxSession->getVariable('sess_challenge');
        $queryBuilder = $this->queryBuilder->create();
        $queryBuilder
                ->select('oxorder_id')
                ->from('oxnets')
                ->where('oxorder_id = :oxorder_id')
                ->setParameters([
                    'oxorder_id' => $sess_id,
        ]);
        $order_id = $queryBuilder->execute()->fetchOne();
        if (!empty($order_id)) {
            $orderId = \OxidEsales\Eshop\Core\UtilsObject::getInstance()->generateUID();
            $this->oxSession->setVariable("sess_challenge", $orderId);
        }
        // finalizing ordering process (validating, storing order into DB, executing payment, setting status ...)
        $oBasket = $this->oxSession->getBasket();
        $sDeliveryAddress = $oUser->getEncodedDeliveryAddress();
        $_POST['sDeliveryAddressMD5'] = $sDeliveryAddress;
        $iSuccess = $this->oxOrder->finalizeOrder($oBasket, $oUser);
        $paymentId = $this->oxSession->getVariable('payment_id');
        $this->oOrder->updateOrdernr($this->oxOrder, $orderNr = null);
        $api_return = $this->oCommonHelper->getCurlResponse($this->oCommonHelper->getApiUrl() . $paymentId, "GET");
        $response = json_decode($api_return, true);
        $this->oDebugHandler->log(" payment api status NetsOrder, response" . $response);
        $refUpdate = [
            'reference' => $orderNr,
            'checkoutUrl' => $response['payment']['checkout']['url']
        ];
        $this->oDebugHandler->log(" payment api status NetsOrder, response checkout url" . $response['payment']['checkout']['url']);
        $this->oDebugHandler->log(" refupdate NetsOrder, response", $refUpdate);
        $this->oCommonHelper->getCurlResponse($this->oCommonHelper->getUpdateRefUrl($paymentId), 'PUT', json_encode($refUpdate));
        $chargeResponse = $this->oCommonHelper->getCurlResponse($this->oCommonHelper->getApiUrl() . $paymentId, 'GET');
        $api_ret = json_decode($chargeResponse, true);
        if (isset($api_ret)) {
            foreach ($api_ret['payment']['charges'] as $ky => $val) {
                foreach ($val['orderItems'] as $key => $value) {
                    if (isset($val['chargeId'])) {

                        $queryBuilder = $this->queryBuilder->create();
                        $queryBuilder->insert('oxnets')
                                ->values(
                                        array(
                                            'transaction_id' => '?',
                                            'charge_id' => '?',
                                            'product_ref' => '?',
                                            'charge_qty' => '?',
                                            'charge_left_qty' => '?'
                                        )
                                )
                                ->setParameter(0, $paymentId)
                                ->setParameter(1, $val['chargeId'])
                                ->setParameter(2, $value['reference'])
                                ->setParameter(3, $value['quantity'])
                                ->setParameter(4, $value['quantity']);
                        $queryData = $queryBuilder->execute();
                    }
                }
            }
        }
        return true;
    }

    /**
     * Function to update order no in oxnets table
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     * @param string The OXID Order hash string
     * @return int $oOrderrnr
     */
    public function updateOrdernr($oxOrder, $orderNr = null)
    {
        $paymentId = $this->oxSession->getVariable('payment_id');
        $queryBuilder = $this->queryBuilder->create();

        if (isset($oxOrder->oxorder__oxordernr->value)) {
            $orderNr = $oxOrder->oxorder__oxordernr->value;
            $this->oDebugHandler->log(" refupdate NetsOrder, order nr" . $oxOrder->oxorder__oxordernr->value);
            $this->oxSession->setVariable('orderNr', $orderNr);
            $queryBuilder
                    ->update('oxnets', 'o')
                    ->set('o.oxordernr', '?')
                    ->set('o.hash', '?')
                    ->set('o.oxorder_id', '?')
                    ->where('o.transaction_id = ?')
                    ->setParameter(0, $orderNr)
                    ->setParameter(1, $this->oxSession->getVariable('sess_challenge'))
                    ->setParameter(2, $this->oxSession->getVariable('sess_challenge'))
                    ->setParameter(3, $paymentId);
        } else {
            $queryBuilder
                    ->update('oxnets', 'o')
                    ->set('o.oxordernr', '?')
                    ->where('o.transaction_id = ?')
                    ->setParameter(0, $orderNr)
                    ->setParameter(1, $paymentId);
        }
        return $queryBuilder->execute();
    }

    /**
     * Function to check if it embedded checkout
     * @return bool
     */
    public function isEmbedded()
    {
        $mode = $this->oxConfig->getConfigParam('nets_checkout_mode');
        $queryBuilder = $this->queryBuilder->create();
        $queryBuilder
                ->select('OXACTIVE')
                ->from('oxpayments')
                ->where('oxid = :oxorder_id')
                ->setParameters([
                    'oxorder_id' => self::MODULE_NAME,
        ]);
        $payMethod = $queryBuilder->execute()->fetchOne();

        if ($mode == "embedded" && $payMethod == 1) {
            return true;
        }
        return false;
    }

    /**
     * Function to get order payment Id
     * @param object The OXID Order object
     * @return object
     */
    public function getOrderPaymentId($orderId)
    {
        $queryBuilder = $this->queryBuilder->create();
        $queryBuilder
                ->select('transaction_id')
                ->from('oxnets')
                ->where('oxorder_id = :oxorder_id')
                ->setParameters([
                    'oxorder_id' => $orderId,
        ]);
        return $queryBuilder->execute()->fetchOne();
    }

}
