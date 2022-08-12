<?php

namespace Es\NetsEasy\Tests\Unit\Models;

use \Es\NetsEasy\ShopExtend\Application\Models\OrderItems;
use Es\NetsEasy\Tests\Unit\Controller\Admin\OrderOverviewControllerTest;
use OxidEsales\Eshop\Core\Field;

class OrderItemsTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $oOrderItems;
    protected $oOrderOverviewControllerTest;

    protected function setUp(): void
    {
        parent::setUp();
        include_once dirname(__FILE__) . "/../../../../../../bootstrap.php";
        $this->oOrderItems = \oxNew(OrderItems::class);
        $this->oOrderOverviewControllerTest = \oxNew(OrderOverviewControllerTest::class);
    }

    /*
     * Test case to get order items to pass capture, refund, cancel api
     */

    public function testGetOrderItems()
    {
        $response = $this->oOrderOverviewControllerTest->getNetsPaymentResponce();
        $response = ['response' => json_decode($response, true)];
        $oOrderItems = $this->getMockBuilder(OrderItems::class)->setMethods(['getItemList'])->getMock();
        $oOrderItems->expects($this->any())->method('getItemList')->willReturn(['items' => [0 => [
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
        ]]]);

        $orderItemsObj = new OrderItems($oOrderItems, null);
        $result = $orderItemsObj->getOrderItems(100);
        $this->assertNotEmpty($result);
    }

    /*
     * Test case to get product item listing
     */

    public function testGetItemList()
    {
        $oOrderItems = $this->getMockBuilder(OrderItems::class)->setMethods(['getOrderItems'])->getMock();
        $oOrderItems->expects($this->any())->method('getOrderItems')->willReturn([
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
        ]);
        $oOrderItems->oxorderarticles__oxartnum = new Field(true);
        $oOrderItems->oxorderarticles__oxtitle = new Field(true);
        $oOrderItems->oxorderarticles__oxamount = new Field(true);
        $oOrderItems->oxorderarticles__oxvat = new Field(true);
        $oOrderItems->oxorderarticles__oxnprice = new Field(true);
        $oOrderItems->oxorderarticles__oxvatprice = new Field(true);
        $oOrderItems->oxorderarticles__oxbrutprice = new Field(true);
        $oOrderItems->oxorderarticles__oxnetprice = new Field(true);
        $oOrderItems->oxorderarticles__oxbprice = new Field(true);

        $orderItemsObj = new OrderItems($oOrderItems, null);
        $result = $orderItemsObj->getItemList($oOrderItems);
        $this->assertNotEmpty($result);
    }

    /*
     * Test case to get greeting card items
     */

    public function testGetGreetingCardItem()
    {
        $oOrderItems = $this->getMockBuilder(OrderItems::class)->setMethods(['getOrderItems'])->getMock();
        $oOrderItems->expects($this->any())->method('getOrderItems')->willReturn([
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
        ]);
        $oOrderItems->oxorder__oxgiftcardcost = new Field(true);
        $oOrderItems->oxorder__oxgiftcardvat = new Field(true);

        $orderItemsObj = new OrderItems($oOrderItems, null);
        $result = $orderItemsObj->getGreetingCardItem($oOrderItems);
        $this->assertNotEmpty($result);
    }

    /*
     * Test case to get Gift Wrapping items
     */

    public function testGetGiftWrappingItem()
    {
        $oOrderItems = $this->getMockBuilder(OrderItems::class)->setMethods(['getOrderItems'])->getMock();
        $oOrderItems->expects($this->any())->method('getOrderItems')->willReturn([
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
        ]);
        $oOrderItems->oxorder__oxwrapcost = new Field(true);
        $oOrderItems->oxorder__oxwrapvat = new Field(true);

        $orderItemsObj = new OrderItems($oOrderItems, null);
        $result = $orderItemsObj->getGiftWrappingItem($oOrderItems);
        $this->assertNotEmpty($result);
    }

    /*
     * Test case to get pay cost items
     */

    public function testGetPayCost()
    {
        $oOrderItems = $this->getMockBuilder(OrderItems::class)->setMethods(['getOrderItems'])->getMock();
        $oOrderItems->expects($this->any())->method('getOrderItems')->willReturn([
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
        ]);
        $oOrderItems->oxorder__oxpaycost = new Field(true);
        $oOrderItems->oxorder__oxpayvat = new Field(true);

        $orderItemsObj = new OrderItems($oOrderItems, null);
        $result = $orderItemsObj->getPayCost($oOrderItems);
        $this->assertNotEmpty($result);
    }

    /*
     * Test case to prepare amount
     */

    public function testPrepareAmount()
    {
        $amount = $this->oOrderItems->prepareAmount(1039);
        $this->assertNotEmpty($amount);
    }

}
