<?php

namespace Es\NetsEasy\ShopExtend\Application\Models;

use Es\NetsEasy\Api\NetsPaymentTypes;
use Es\NetsEasy\Api\NetsLog;

/**
 * Class defines execution of nets payment.
 */
class PaymentGateway
{

    protected $_NetsLog = false;
    protected $netsLog;
    protected $netsPaymentTypes;
    /**
     * Function to execute Nets payment.
     * @return bool
     */
    public function executePayment($dAmount, &$oOrder)
    {
        $this->_NetsLog = \oxRegistry::getConfig()->getConfigParam('nets_blDebug_log');
        $this->netsLog = \oxNew(NetsLog::class);
        $this->netsPaymentTypes = \oxNew(NetsPaymentTypes::class);
        // $ox_payment_id = $this->getSession()->getInstance()->getBasket()->getPaymentId();
        $ox_payment_id = \oxRegistry::getSession()->getBasket()->getPaymentId();
        $payment_type = $this->netsPaymentTypes->getNetsPaymentType($ox_payment_id);
        $this->netsLog->log($this->_NetsLog, "NetsPaymentGateway executePayment: " . $payment_type);
        if ((!isset($payment_type) || !$payment_type) && $dAmount != 'test') {
            $this->netsLog->log($this->_NetsLog, "NetsPaymentGateway executePayment, parent");
            return parent::executePayment($dAmount, $oOrder);
        }
        $this->netsLog->log($this->_NetsLog, "NetsPaymentGateway executePayment");
        $success = true;
        \oxRegistry::getSession()->deleteVariable('nets_success');
        if (isset($success) && $success === true) {
            $this->netsLog->log($this->_NetsLog, "NetsPaymentGateway executePayment - success");
            return true;
        }
        $this->netsLog->log($this->_NetsLog, "NetsPaymentGateway executePayment - failure");
        return false;
    }

}
