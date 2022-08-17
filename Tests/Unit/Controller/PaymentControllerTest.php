<?php

namespace Es\NetsEasy\Tests\Unit\Controller;

use \Es\NetsEasy\ShopExtend\Application\Controller\PaymentController;

class PaymentControllerTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $paymentObject;

    protected function setUp(): void
    {
        include_once dirname(__FILE__) . "/../../../../../../bootstrap.php";
        $this->paymentObject = \oxNew(PaymentController::class);
        parent::setUp();
    }

    /**
     * Test case to get Nets Payment Types from db
     */
    public function testGetNetsPaymentTypes()
    {
        $netsPaymentTypes = $this->paymentObject->getNetsPaymentTypes();
        if ($netsPaymentTypes) {
            $this->assertNotEmpty($netsPaymentTypes);
        } else {
            $this->assertNull($netsPaymentTypes);
        }
    }

    /**
     * Test case for get nets payment text
     */
    public function testGetPaymentTextConfig()
    {
        $paymentText = $this->paymentObject->getPaymentTextConfig();
        if ($paymentText) {
            $this->assertNotNull($paymentText);
        } else {
            $this->assertNull($paymentText);
        }
    }

    /**
     * Test case for get nets payment text
     */
    public function testGetPaymentUrlConfig()
    {
        $paymentUrl = $this->paymentObject->getPaymentUrlConfig();
        $this->assertStringStartsWith('http://easymoduler.dk', $paymentUrl);
    }

}
