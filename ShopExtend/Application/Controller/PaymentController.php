<?php

namespace Es\NetsEasy\ShopExtend\Application\Controller;

use Es\NetsEasy\Api\NetsPaymentTypes;
use Es\NetsEasy\ShopExtend\Application\Models\Payment as NetsPayment;

/**
 * Class defines description of nets payment
 */
class PaymentController extends PaymentController_parent
{

    var $payment_types_active;
    protected $netsPaymentTypes;

    /**
     * Function to initialize the class 
     * @return null
     */
    public function init()
    {
        $oxSession = \oxNew(\OxidEsales\EshopCommunity\Core\Session::class);
        $oxSession->deleteVariable('nets_err_msg');
        $this->getNetsPaymentTypes();
        $this->_sThisTemplate = parent::render();
        parent::init();
    }

    /**
     * Function to get Nets Payment Types from db
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     * @return Null
     */
    public function getNetsPaymentTypes()
    {
        $this->payment_types_active = [];
        $netsPaymentObj = \oxNew(NetsPayment::class, \oxNew(\Es\NetsEasy\Core\CommonHelper::class));
        $active_payment_ids = $netsPaymentObj->getActivePayments();
        if (!empty($active_payment_ids)) {
            $payment_types = [];
            $netsPaymentTypesObj = \oxNew(NetsPaymentTypes::class);
            foreach ($active_payment_ids as $payment_id) {
                $payment_type = $netsPaymentTypesObj->getNetsPaymentType($payment_id[0]);
                if (isset($payment_type) && $payment_type) {
                    $payment_types[] = $payment_type;
                }
            }
            $this->payment_types_active = $payment_types;
        }
    }

    /**
     * Function to get nets payment text
     * @return array
     */
    public function getPaymentTextConfig()
    {
        $oxConfig = \oxNew(\OxidEsales\EshopCommunity\Core\Config::class);
        return $oxConfig->getConfigParam('nets_payment_text');
    }

    /**
     * Function to get nets payment text
     * @return string
     */
    public function getPaymentUrlConfig()
    {
        $oxConfig = \oxNew(\OxidEsales\EshopCommunity\Core\Config::class);
        return $oxConfig->getConfigParam('nets_payment_url');
    }

}
