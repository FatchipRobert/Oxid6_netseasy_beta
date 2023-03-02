<?php

namespace Es\NetsEasy\Application\Helper;

use OxidEsales\Eshop\Application\Model\Order as CoreOrder;
use OxidEsales\Eshop\Core\Registry;

class Order
{
    /**
     * @var Order
     */
    protected static $oInstance = null;

    /**
     * Create singleton instance of current helper class
     *
     * @return Order
     */
    public static function getInstance()
    {
        if (self::$oInstance === null) {
            self::$oInstance = oxNew(self::class);
        }
        return self::$oInstance;
    }

    /**
     * Cancels the current order because it failed for example because the customer canceled the payment
     *
     * @return void
     */
    public function cancelCurrentOrder()
    {
        $sSessChallenge = Registry::getSession()->getVariable('sess_challenge');

        $oOrder = oxNew(CoreOrder::class);
        if ($oOrder->load($sSessChallenge) === true) {
            if ($oOrder->oxorder__oxtransstatus->value != 'OK') {
                $oOrder->cancelOrder();
            }
        }
        Registry::getSession()->deleteVariable('sess_challenge');
    }
}