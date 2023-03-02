<?php

namespace Es\NetsEasy\extend\Application\Controller;

/**
 * Class Extending thank you controller for adding payment id in front end
 */
class ThankyouController extends ThankyouController_parent
{
    /**
     * Get payment id from database to display in thank you page.
     *
     * @return string
     */
    public function netsGetTransactionId()
    {
        return $this->getOrder()->oxorder__oxtransid->value;
    }
}
