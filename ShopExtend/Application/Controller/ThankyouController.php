<?php

namespace Es\NetsEasy\ShopExtend\Application\Controller;

use Es\NetsEasy\ShopExtend\Application\Models\Order as NetsOrder;

/**
 * Class Extending thank you controller for adding payment id in front end
 */
class ThankyouController extends ThankyouController_parent
{

    protected $oOrder;

    /**
     * Get payment id from database to display in thank you page.
     * @param  string $oOrder The order model object 
     * @return string paymentId
     */
    public function getPaymentId($oOrder = null)
    {
        $oxSession = \oxNew(\OxidEsales\EshopCommunity\Core\Session::class);
        $oxSession->deleteVariable('payment_id');
        $oxSession->deleteVariable('sess_challenge');
        $oxSession->deleteVariable('orderNr');
        if ($oOrder) {
            $oOrder = $oOrder->getOrder();
        } else {
            $oOrder = $this->getOrder();
        }
        $objOrder = \oxNew(NetsOrder::class, null, \oxNew(\Es\NetsEasy\Core\CommonHelper::class), null, \oxNew(\OxidEsales\Eshop\Application\Model\Order::class), \oxNew(\Es\NetsEasy\ShopExtend\Application\Models\BasketItems::class), \oxNew(\Es\NetsEasy\ShopExtend\Application\Models\Payment::class, \oxNew(\Es\NetsEasy\Core\CommonHelper::class)), \oxNew(\Es\NetsEasy\ShopExtend\Application\Models\Address::class));
        return $objOrder->getOrderPaymentId($oOrder->oxorder__oxid->value);
    }

}
