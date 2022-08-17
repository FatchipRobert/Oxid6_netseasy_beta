<?php

namespace Es\NetsEasy\ShopExtend\Application\Controller;

use Es\NetsEasy\Api\NetsLog;
use Es\NetsEasy\ShopExtend\Application\Models\Order as NetsOrder;
use Es\NetsEasy\Core\CommonHelper;
use OxidEsales\Eshop\Core\Registry;
use Es\NetsEasy\ShopExtend\Application\Models\Payment;

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

    protected $_NetsLog = false;
    protected $oCommonHelper = false;
    protected $oNetsOrder;
    protected $oxUtils;
    protected $netsLog;
    protected $payment;

    /**
     * Constructor
     */
    public function __construct($oNetsOrder = null, $commonHelper = null, $oxUtils = null)
    {
        $this->_NetsLog = Registry::getConfig()->getConfigParam('nets_blDebug_log');
        $this->netsLog = \oxNew(NetsLog::class);
        $this->netsLog->log($this->_NetsLog, "NetsOrderController, constructor");
        $this->payment = \oxNew(payment::class);
        if (!$commonHelper) {
            $this->oCommonHelper = \oxNew(CommonHelper::class);
        } else {
            $this->oCommonHelper = $commonHelper;
        }

        if (!$oNetsOrder) {
            $this->oNetsOrder = \oxNew(NetsOrder::class);
        } else {
            $this->oNetsOrder = $oNetsOrder;
        }

        if (!$oxUtils) {
            $this->oxUtils = Registry::getUtils();
        } else {
            $this->oxUtils = $oxUtils;
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public function execute()
    {
        $this->netsLog->log($this->_NetsLog, "NetsOrderController, execute");
        $oBasket = $this->getSession()->getBasket();
        $oUser = $this->getUser();
        if ($oBasket->getProductsCount()) {
            try {
                if ($this->oNetsOrder->isEmbedded()) {
                    //finalizing ordering process (validating, storing order into DB, executing payment, setting status 
                    $this->oNetsOrder->processOrder($oUser);
                    return $this->oxUtils->redirect(Registry::getConfig()
                                            ->getSslShopUrl() . 'index.php?cl=thankyou');
                } else {
                    $this->getPaymentApiResponse();
                }
            } catch (\Exception $e) {
                Registry::getUtilsView()->addErrorToDisplay($e->getMessage(), false, true, 'basket');
            }
        }
    }

    /**
     * Function to get error message displayed on template file
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->getSession()->getVariable('nets_err_msg');
    }

    /**
     * Function to get basket amount
     * @return amount
     */
    public function getBasketAmount()
    {
        $mySession = $this->getSession();
        $oBasket = $mySession->getBasket();
        $returnValue = null;
        if (!empty($oBasket->getPrice()->getBruttoPrice())) {
            $returnValue = intval(strval(($oBasket->getPrice()->getBruttoPrice() * 100)));
        }
        return $returnValue;
    }

    /**
     * Function to get return data after hosted payment checkout is done
     * @return null
     */
    public function returnhosted()
    {
        $paymentId = Registry::getSession()->getVariable('payment_id');
        $chargeResponse = $this->oCommonHelper->getCurlResponse($this->oCommonHelper->getApiUrl() . $paymentId, 'GET');
        $api_ret = json_decode($chargeResponse, true);
        $this->payment->savePaymentDetails($api_ret, $paymentId);
        return $this->oxUtils->redirect($this->getConfig()
                                ->getSslShopUrl() . 'index.php?cl=thankyou&paymentid=' . $paymentId);
    }

    /*
     * Function to get checkout js url based on environment i.e live or test
     * @return checkout js url
     */

    public function getCheckoutJs()
    {
        if ($this->getConfig()->getConfigParam('nets_blMode') == 0) {
            return self::JS_ENDPOINT_TEST;
        }
        return self::JS_ENDPOINT_LIVE;
    }

    /*
     * Function to get payment api response and pass it to template
     * @return payment id
     */

    public function getPaymentApiResponse()
    {
        // additional user check
        $oUser = $this->getUser();
        $returnValue = true;
        $oBasket = $this->getSession()->getBasket();
        if ($oBasket->getProductsCount()) {
            $oOrder = \oxNew(\OxidEsales\Eshop\Application\Model\Order::class);
            // finalizing ordering process (validating, storing order into DB, executing payment, setting status ...)
            $iSuccess = $oOrder->finalizeOrder($oBasket, $oUser);
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
     * @return checkout key
     */

    public function getCheckoutKey()
    {
        return $this->oCommonHelper->getCheckoutKey();
    }

    /*
     * Function to compile layout style file url for the embedded checkout type
     * @return layout style
     */

    public function getLayout()
    {
        return $this->oCommonHelper->getLayout();
    }

}
