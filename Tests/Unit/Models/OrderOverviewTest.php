<?php

namespace Es\NetsEasy\Tests\Unit\Models;

use \Es\NetsEasy\ShopExtend\Application\Models\OrderOverview;
use OxidEsales\Eshop\Core\Field;

class OrderOverviewTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $oOrderOverviewObject;

    protected function setUp(): void
    {
        parent::setUp();
        include_once dirname(__FILE__) . "/../../../../../../bootstrap.php";
        $this->oOrderOverviewObject = \oxNew(OrderOverview::class);
    }

    /*
     * Test case to get shopping cost
     */

    public function testGetShoppingCost()
    {
        $oOrderOverview = $this->getMockBuilder(OrderOverview::class)->setMethods(['getOrderItems'])->getMock();
        $oOrderOverview->expects($this->any())->method('getOrderItems')->willReturn(array(
            'reference' => '1205',
            'name' => 'ABC',
            'quantity' => 1,
            'unit' => 'units',
            'unitPrice' => 10000,
            'taxRate' => 2500,
            'taxAmount' => 250,
            'grossTotalAmount' => 12500,
            'netTotalAmount' => 10000,
            'oxbprice' => 10000
        ));
        $oOrderOverview->oxorder__oxdelcost = new Field(true);
        $oOrderOverview->oxorder__oxdelvat = new Field(true);

        $orderOverviewObj = new OrderOverview($oOrderOverview, null);
        $result = $orderOverviewObj->getShoppingCost($oOrderOverview);
        $this->assertNotEmpty($result);
    }

    /*
     * Test case to fetch payment method type from database table oxorder
     */

    public function testGetPaymentMethod()
    {
        $paymentMethod = $this->oOrderOverviewObject->getPaymentMethod(100);
        $this->assertFalse($paymentMethod);
    }
    
    /*
     * Test case to prepare amount
     */

    public function testPrepareAmount()
    {
        $amount = $this->oOrderOverviewObject->prepareAmount(1039);
        $this->assertNotEmpty($amount);
    }

}
