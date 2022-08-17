<?php

namespace Es\NetsEasy\ShopExtend\Application\Controller;
use OxidEsales\EshopCommunity\Core\Request;
use Es\NetsEasy\ShopExtend\Application\Models\Payment;
use Es\NetsEasy\Core\CommonHelper;
use OxidEsales\EshopCommunity\Core\Registry;
use Es\NetsEasy\ShopExtend\Application\Models\Order;
/**
 * Class Extending thank you controller for adding payment id in front end
 */
class ThankyouController extends  ThankyouController_parent
{

    protected $oOrder;
 
    /**
     * Get payment id from database to display in thank you page.
     *
     * @return $paymentId
     */
    public function getPaymentId($oOrder = null)
    {
        if ($oOrder) {
            $oOrder = $oOrder->getOrder();
        } else {
            $oOrder = $this->getOrder();
        }
        $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
        $sSQL_select = "SELECT transaction_id FROM oxnets WHERE oxorder_id = ? LIMIT 1";
        return $oDB->getOne($sSQL_select, [
                    $oOrder->oxorder__oxid->value
        ]);
    }

}
