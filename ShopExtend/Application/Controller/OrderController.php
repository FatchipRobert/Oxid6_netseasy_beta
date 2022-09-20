<?php

namespace Es\NetsEasy\ShopExtend\Application\Controller;

use Es\NetsEasy\ShopExtend\Application\Models\Order as NetsOrder;
use Es\NetsEasy\Core\CommonHelper;
use OxidEsales\Eshop\Core\Registry;
use Es\NetsEasy\ShopExtend\Application\Models\Payment;
use Es\NetsEasy\ShopExtend\Application\Models\Address;
use OxidEsales\Eshop\Application\Model\Order;
use Es\NetsEasy\ShopExtend\Application\Models\BasketItems;
use Es\NetsEasy\Core\DebugHandler;

/**
 * Class controls nets payment process
 * It also shows the nets embedded checkout window
 */
class OrderController extends OrderController_parent
{

    const ENDPOINT_TEST = 'https://test.api.dibspayment.eu/v1/payments/';
    const ENDPOINT_LIVE = 'https://api.dibspayment.eu/v1/payments/';
    const JS_ENDPOINT_TEST = "https://test.checkout.dibspayment.eu/v1/checkout.js";
    const JS_ENDPOINT_LIVE = "https://checkout.dibspayment.eu/v1/checkout.js";
    const MODULE_NAME = "nets_easy";

    protected $oCommonHelper;
    protected $oNetsOrder;
    protected $oxUtils;
    protected $netsLog;
    protected $payment;
    protected $oxConfig;
    protected $oxSession;
    protected $oxBasket;
    protected $oDebugHandler;

    /**
     * Constructor
     * @param  object $oNetsOrder The Order model injected object
     * @param  object $commonHelper The service file injected as object
     * @param  object $oxUtils The oxid Utils injected object
     * @param  object $oxBasket The Order basket Model injected object
     * @return Null
     */
    public function __construct($oNetsOrder = null, $commonHelper = null, $oxUtils = null, $oxBasket = null)
    {
        $this->oDebugHandler = \oxNew(DebugHandler::class);
        $this->oDebugHandler->log("error monolog, constructor");
        $this->oxConfig = \oxNew(\OxidEsales\EshopCommunity\Core\Config::class);
        $this->oxSession = \oxNew(\OxidEsales\EshopCommunity\Core\Session::class);
        $this->oDebugHandler->log("NetsOrderController, constructor");
        if (!$commonHelper) {
            $this->oCommonHelper = \oxNew(CommonHelper::class);
        } else {
            $this->oCommonHelper = $commonHelper;
        }

        $objOrder = \oxNew(Order::class);
        $objAddress = \oxNew(Address::class);
        $this->payment = \oxNew(Payment::class, $this->oCommonHelper);
        $objBasketItems = \oxNew(BasketItems::class);

        $this->_NetsLog = $this->oxConfig->getConfigParam('nets_blDebug_log');

        if (!$oxUtils) {
            $this->oxUtils = Registry::getUtils();
        } else {
            $this->oxUtils = $oxUtils;
        }
        if (!$oxBasket) {
            $this->oxBasket = $this->oxSession->getBasket();
        } else {
            $this->oxBasket = $oxBasket;
        }

        if (!$oNetsOrder) {
            $this->oNetsOrder = new NetsOrder(null, $this->oCommonHelper, null, $objOrder, $objBasketItems, $this->payment, $objAddress);
        } else {
            $this->oNetsOrder = $oNetsOrder;
        }
    }

    /**
     * The function executes order checkout process
     * @return Null
     */
    public function execute()
    {		
        if ($this->oxSession->getVariable("paymentid") == "nets_easy") {
            $this->oDebugHandler->log("NetsOrderController, execute");			
            $oUser = $this->getUser();
            if ($this->oxBasket->getProductsCount()) {
                try {
                    if ($this->oNetsOrder->isEmbedded()) {
                        //finalizing ordering process (validating, storing order into DB, executing payment, setting status 
                        $this->oNetsOrder->processOrder($oUser);
                        return $this->oxUtils->redirect($this->oxConfig
                                                ->getSslShopUrl() . 'index.php?cl=thankyou');
                    } else {
                        $this->getPaymentApiResponse();
                    }
                } catch (\Exception $e) {
                    Registry::getUtilsView()->addErrorToDisplay($e->getMessage(), false, true, 'basket');
                }
            }
        } else {
            return parent::execute();
        }
    }

    /**
     * Function to get error message displayed on template file
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->oxSession->getVariable('nets_err_msg');
    }

    /**
     * Function to get basket amount
     * @return int amount
     */
    public function getBasketAmount()
    {

        $returnValue = null;
        if (!empty($this->oxBasket->getPrice()->getBruttoPrice())) {
            $returnValue = intval(strval(($this->oxBasket->getPrice()->getBruttoPrice() * 100)));
        }
        return $returnValue;
    }

    /**
     * Function to get return data after hosted payment checkout is done
     * @return null
     */
    public function returnhosted()
    {
        $paymentId = $this->oxSession->getVariable('payment_id');
		$orderNo = $this->oxSession->getVariable('orderNr');
        $chargeResponse = $this->oCommonHelper->getCurlResponse($this->oCommonHelper->getApiUrl() . $paymentId, 'GET');
        $api_ret = json_decode($chargeResponse, true);
        $this->payment->savePaymentDetails($api_ret, $paymentId);
		$this->oNetsOrder->updateOrdernr(null,$orderNo);		
        return $this->oxUtils->redirect($this->oxConfig
                                ->getSslShopUrl() . 'index.php?cl=thankyou&paymentid=' . $paymentId);
    }

    /*
     * Function to get checkout js url based on environment i.e live or test
     * @return string checkout js url
     */

    public function getCheckoutJs()
    {
        if ($this->oxConfig->getConfigParam('nets_blMode') == 0) {
            return self::JS_ENDPOINT_TEST;
        }
        return self::JS_ENDPOINT_LIVE;
    }

    /*
     * Function to get payment api response and pass it to template
     * @return string payment id
     */

    public function getPaymentApiResponse()
    {
        // additional user check
        $oUser = $this->getUser();
        $returnValue = true;
        if ($this->oxBasket->getProductsCount()) {
            $oOrder = \oxNew(\OxidEsales\Eshop\Application\Model\Order::class);
            // finalizing ordering process (validating, storing order into DB, executing payment, setting status ...)
            $iSuccess = $oOrder->finalizeOrder($this->oxBasket, $oUser);
            // performing special actions after user finishes order (assignment to special user groups)
            if ($oOrder) {
                $returnValue = $this->oNetsOrder->createNetsTransaction($oOrder);
            }
        }
        return $returnValue;
    }

    /**
     * Function to check if it embedded checkout
     * @return bool
     */
    public function isEmbedded()
    {
        return $this->oNetsOrder->isEmbedded();
    }

    /*
     * Function to fetch checkout key to pass in checkout js options based on environment live or test
     * @return string checkout key
     */

    public function getCheckoutKey()
    {

        return $this->oCommonHelper->getCheckoutKey();
    }

    /*
     * Function to compile layout style file url for the embedded checkout type
     * @return string layout style
     */

    public function getLayout()
    {
        return $this->oCommonHelper->getLayout();
    }

}
