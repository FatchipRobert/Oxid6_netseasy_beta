<?php

namespace Es\NetsEasy\extend\Application\Model;

use Es\NetsEasy\Application\Helper\Payment as PaymentHelper;

class Payment extends Payment_parent
{
    /**
     * Returns if current payment method is a Nets method
     *
     * @return bool
     */
    public function netsIsNetsPaymentUsed()
    {
        return PaymentHelper::getInstance()->isNetsPayment($this->getId());
    }
}