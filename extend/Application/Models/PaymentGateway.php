<?php

namespace Es\NetsEasy\extend\Application\Models;

use Es\NetsEasy\Api\NetsPaymentTypes;
use Es\NetsEasy\Api\NetsLog;

/**
 * Class defines execution of nets payment.
 */
class PaymentGateway
{

    protected $_NetsLog = false;

    /**
     * Function to execute Nets payment.
     * @return bool
     */
    public function executePayment($dAmount, &$oOrder)
    {
        $this->_NetsLog = \oxRegistry::getConfig()->getConfigParam('nets_blDebug_log');
        // $ox_payment_id = $this->getSession()->getInstance()->getBasket()->getPaymentId();
        $ox_payment_id = \oxRegistry::getSession()->getBasket()->getPaymentId();
        $payment_type = netsPaymentTypes::getNetsPaymentType($ox_payment_id);
        NetsLog::log($this->_NetsLog, "NetsPaymentGateway executePayment: " . $payment_type);
        if ((!isset($payment_type) || !$payment_type) && $dAmount != 'test') {
            NetsLog::log($this->_NetsLog, "NetsPaymentGateway executePayment, parent");
            return parent::executePayment($dAmount, $oOrder);
        }
        NetsLog::log($this->_NetsLog, "NetsPaymentGateway executePayment");
        $success = true;
        \oxRegistry::getSession()->deleteVariable('nets_success');
        if (isset($success) && $success === true) {
            NetsLog::log($this->_NetsLog, "NetsPaymentGateway executePayment - success");
            return true;
        }
        NetsLog::log($this->_NetsLog, "NetsPaymentGateway executePayment - failure");
        return false;
    }

}
