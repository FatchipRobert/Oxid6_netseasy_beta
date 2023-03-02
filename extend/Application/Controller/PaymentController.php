<?php

namespace Es\NetsEasy\extend\Application\Controller;

use Es\NetsEasy\Application\Helper\Order as OrderHelper;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class defines description of nets payment
 */
class PaymentController extends PaymentController_parent
{
    /**
     * Function to initialize the class
     *
     * @return void
     */
    public function init()
    {
        $sSessChallenge = Registry::getSession()->getVariable('sess_challenge');
        $blIsUserRedirected = Registry::getSession()->getVariable('netsCustomerIsRedirected');
        if (!empty($sSessChallenge) && $blIsUserRedirected === true) {
            OrderHelper::getInstance()->cancelCurrentOrder();
        }
        Registry::getSession()->deleteVariable('netsCustomerIsRedirected');
        Registry::getSession()->deleteVariable('nets_err_msg');

        parent::init();
    }

    /**
     * Function to get nets payment text
     *
     * @return string
     */
    public function netsGetPaymentUrlConfig()
    {
        return Registry::getConfig()->getConfigParam('nets_payment_url');
    }
}
