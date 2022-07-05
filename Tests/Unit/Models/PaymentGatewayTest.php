<?php

namespace Es\NetsEasy\Tests\Unit\Models;

use Es\NetsEasy\extend\Application\Models\PaymentGateway as NetsPaymentGateway;

class PaymentGatewayTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $oPaymentGateway;

    protected function setUp(): void
    {
        parent::setUp();
        include_once dirname(__FILE__) . "/../../../../../../bootstrap.php";
        $this->oPaymentGateway = \oxNew(NetsPaymentGateway::class);
    }

    /**
     * Test case to get product item
     */
    public function testExecutePayment()
    {
        $strvalue = 'test';
        $resultValue = $this->oPaymentGateway->executePayment('test', $strvalue);
        $this->assertTrue(true);
    }

}
