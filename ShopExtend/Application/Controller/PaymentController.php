<?php

namespace Es\NetsEasy\ShopExtend\Application\Controller;

use Es\NetsEasy\Api\NetsPaymentTypes;
use OxidEsales\EshopCommunity\Core\Registry;

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
        $this->getSession()->deleteVariable('nets_err_msg');
        $this->getNetsPaymentTypes();
        $this->_sThisTemplate = parent::render();
        parent::init();
    }

    /**
     * Function to get Nets Payment Types from db
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    public function getNetsPaymentTypes()
    {
        $this->payment_types_active = [];
        $netsPaymentTypesObj = \oxNew(NetsPaymentTypes::class);
        $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
        $sSql = "SELECT OXID FROM oxpayments WHERE oxactive = 1";
        $active_payment_ids = $oDB->getAll($sSql);
        if (!empty($active_payment_ids)) {
            $payment_types = [];
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
        return $this->getConfig()->getConfigParam('nets_payment_text');
    }

    /**
     * Function to get nets payment text
     * @return string
     */
    public function getPaymentUrlConfig()
    {
        return $this->getConfig()->getConfigParam('nets_payment_url');
    }

}
