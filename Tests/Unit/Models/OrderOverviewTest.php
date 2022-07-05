<?php

namespace Es\NetsEasy\Tests\Unit\Models;

use OxidEsales\Eshop\Application\Model\Basket;
use \Es\NetsEasy\extend\Application\Models\OrderOverview;
use Es\NetsEasy\Api\NetsLog;
use Es\NetsEasy\Core\CommonHelper;
use Es\NetsEasy\Tests\Unit\Controller\Admin\OrderOverviewControllerTest;
use OxidEsales\Eshop\Core\Field;

class OrderOverviewTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $oOrderOverviewObject;
    protected $oOrderOverviewControllerTest;

    protected function setUp(): void
    {
        parent::setUp();
        include_once dirname(__FILE__) . "/../../../../../../bootstrap.php";
        $this->oOrderOverviewObject = \oxNew(OrderOverview::class);
        $this->oOrderOverviewControllerTest = \oxNew(OrderOverviewControllerTest::class);
    }

    /**
     * Test case to check the nets payment status and display in admin order list backend page
     */
    public function testGetEasyStatus()
    {
        $oOrderOverview = $this->getMockBuilder(OrderOverview::class)->setMethods(['getOrderItems', 'getPaymentStatus'])->getMock();
        $oOrderOverview->expects($this->any())->method('getOrderItems')->willReturn(array(
            'totalAmt' => 100,
            'items' => 'items'
        ));
        $oOrderOverview->expects($this->any())->method('getPaymentStatus')->willReturn(array('dbPayStatus' => true));

        $oCommonHelper = $this->getMockBuilder(CommonHelper::class)->setMethods(['getCurlResponse', 'getVoidPaymentUrl', 'getPaymentId', 'getApiUrl'])->getMock();
        $oCommonHelper->expects($this->any())->method('getCurlResponse')->willReturn("{'chargeId':'dummyChargeId'}");
        $oCommonHelper->expects($this->any())->method('getVoidPaymentUrl')->willReturn('url');
        $oCommonHelper->expects($this->any())->method('getPaymentId')->willReturn(true);
        $oCommonHelper->expects($this->any())->method('getApiUrl')->willReturn('url');

        $orderOverviewObj = new OrderOverview($oOrderOverview, $oCommonHelper);
        $result = $orderOverviewObj->getEasyStatus(100);
        $this->assertNotEmpty($result);

        $oCommonHelper = $this->getMockBuilder(CommonHelper::class)->setMethods(['getCurlResponse', 'getVoidPaymentUrl', 'getPaymentId', 'getApiUrl'])->getMock();
        $oCommonHelper->expects($this->any())->method('getCurlResponse')->willReturn("{'chargeId':'dummyChargeId'}");
        $oCommonHelper->expects($this->any())->method('getVoidPaymentUrl')->willReturn('url');
        $oCommonHelper->expects($this->any())->method('getPaymentId')->willReturn(null);
        $oCommonHelper->expects($this->any())->method('getApiUrl')->willReturn('url');

        $orderOverviewObj = new OrderOverview($oOrderOverview, $oCommonHelper);
        $result = $orderOverviewObj->getEasyStatus(100);
        $this->assertNotEmpty($result);
    }

    /**
     * Test case to get payment status
     */
    public function testGetPaymentStatus()
    {
        $response = $this->oOrderOverviewControllerTest->getNetsPaymentResponce();

        $result = $this->oOrderOverviewObject->getPaymentStatus(json_decode($response, true), 100);
        if ($result) {
            $this->assertNotEmpty($result);
            $this->assertArrayHasKey('payStatus', $result);
        }

        $response = json_decode($response, true);
        $response['payment']['summary']['reservedAmount'] = 1233;
        $result = $this->oOrderOverviewObject->getPaymentStatus($response, 100);
        if ($result) {
            $this->assertNotEmpty($result);
            $this->assertArrayHasKey('payStatus', $result);
        }
    }

    /**
     * Test case to capture nets transaction - calls Charge API
     */
    public function testGetOrderCharge()
    {
        $_POST['oxorderid'] = 100;
        $_POST['orderno'] = '65';
        $_POST['reference'] = "1205";
        $_POST['charge'] = 1;

        $oOrderOverview = $this->getMockBuilder(OrderOverview::class)->setMethods(['getOrderItems', 'getValueItem'])->getMock();
        $oOrderOverview->expects($this->any())->method('getOrderItems')->willReturn(array('items' => array(0 => array(
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
        ))));
        $oOrderOverview->expects($this->any())->method('getValueItem')->willReturn([
            'reference' => 'reference',
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

        $oCommonHelper = $this->getMockBuilder(CommonHelper::class)->setMethods(['getCurlResponse', 'getVoidPaymentUrl', 'getPaymentId', 'getApiUrl'])->getMock();
        $oCommonHelper->expects($this->any())->method('getCurlResponse')->willReturn("{'chargeId':'dummyChargeId'}");
        $oCommonHelper->expects($this->any())->method('getVoidPaymentUrl')->willReturn('url');
        $oCommonHelper->expects($this->any())->method('getPaymentId')->willReturn(true);
        $oCommonHelper->expects($this->any())->method('getApiUrl')->willReturn('url');

        $orderOverviewObj = new OrderOverview($oOrderOverview, $oCommonHelper);
        $result = $orderOverviewObj->getOrderCharged(100);
        $this->assertTrue($result);
    }

    /*
     * Test case to get value item list for charge
     * return int
     */

    public function testGetValueItem()
    {
        $result = $this->oOrderOverviewObject->getValueItem(array("cancelledAmount" => 100, "oxbprice" => 10, "taxRate" => 2000), 100);
        if ($result) {
            $this->assertNotEmpty($result);
            $this->assertArrayHasKey('cancelledAmount', $result);
        }
    }

    /*
     * Test case to get order refund
     */

    public function testGetOrderRefund()
    {
        $_POST['oxorderid'] = 100;
        $_POST['orderno'] = '65';
        $_POST['reference'] = "";
        $_POST['charge'] = 1;
        $response = $this->oOrderOverviewControllerTest->getNetsPaymentResponce();
        $response = array('response' => json_decode($response, true));
        $oOrderOverview = $this->getMockBuilder(OrderOverview::class)->setMethods(['getOrderItems', 'getChargeId', 'getItemForRefund'])->getMock();
        $oOrderOverview->expects($this->any())->method('getOrderItems')->willReturn(array('items' => array(0 => array(
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
        ))));
        $oOrderOverview->expects($this->any())->method('getChargeId')->willReturn($response);
        $oOrderOverview->expects($this->any())->method('getItemForRefund')->willReturn([
            'reference' => 'reference',
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

        $oCommonHelper = $this->getMockBuilder(CommonHelper::class)->setMethods(['getCurlResponse', 'getVoidPaymentUrl', 'getPaymentId', 'getApiUrl'])->getMock();
        $oCommonHelper->expects($this->any())->method('getCurlResponse')->willReturn("{'chargeId':'dummyChargeId'}");
        $oCommonHelper->expects($this->any())->method('getVoidPaymentUrl')->willReturn('url');
        $oCommonHelper->expects($this->any())->method('getPaymentId')->willReturn(true);
        $oCommonHelper->expects($this->any())->method('getApiUrl')->willReturn('url');

        $orderOverviewObj = new OrderOverview($oOrderOverview, $oCommonHelper);
        $result = $orderOverviewObj->getOrderRefund();
        $this->assertNull($result);
    }

    /*
     * Test case to get order items to pass capture, refund, cancel api
     */

    public function testGetOrderItems()
    {
        $response = $this->oOrderOverviewControllerTest->getNetsPaymentResponce();
        $response = array('response' => json_decode($response, true));
        $oOrderOverview = $this->getMockBuilder(OrderOverview::class)->setMethods(['getItemList'])->getMock();
        $oOrderOverview->expects($this->any())->method('getItemList')->willReturn(array('items' => array(0 => array(
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
        ))));

        $orderOverviewObj = new OrderOverview($oOrderOverview, null);
        $result = $orderOverviewObj->getOrderItems(100);
        $this->assertNotEmpty($result);
    }

    /*
     * Test case to get product item listing
     */

    public function testGetItemList()
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
        $oOrderOverview->oxorderarticles__oxartnum = new Field(true);
        $oOrderOverview->oxorderarticles__oxtitle = new Field(true);
        $oOrderOverview->oxorderarticles__oxamount = new Field(true);
        $oOrderOverview->oxorderarticles__oxvat = new Field(true);
        $oOrderOverview->oxorderarticles__oxnprice = new Field(true);
        $oOrderOverview->oxorderarticles__oxvatprice = new Field(true);
        $oOrderOverview->oxorderarticles__oxbrutprice = new Field(true);
        $oOrderOverview->oxorderarticles__oxnetprice = new Field(true);
        $oOrderOverview->oxorderarticles__oxbprice = new Field(true);

        $orderOverviewObj = new OrderOverview($oOrderOverview, null);
        $result = $orderOverviewObj->getItemList($oOrderOverview);
        $this->assertNotEmpty($result);
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
     * Test case to get greeting card items
     */

    public function testGetGreetingCardItem()
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
        $oOrderOverview->oxorder__oxgiftcardcost = new Field(true);
        $oOrderOverview->oxorder__oxgiftcardvat = new Field(true);

        $orderOverviewObj = new OrderOverview($oOrderOverview, null);
        $result = $orderOverviewObj->getGreetingCardItem($oOrderOverview);
        $this->assertNotEmpty($result);
    }

    /*
     * Test case to get Gift Wrapping items
     */

    public function testGetGiftWrappingItem()
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
        $oOrderOverview->oxorder__oxwrapcost = new Field(true);
        $oOrderOverview->oxorder__oxwrapvat = new Field(true);

        $orderOverviewObj = new OrderOverview($oOrderOverview, null);
        $result = $orderOverviewObj->getGiftWrappingItem($oOrderOverview);
        $this->assertNotEmpty($result);
    }

    /*
     * Test case to get pay cost items
     */

    public function testGetPayCost()
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
        $oOrderOverview->oxorder__oxpaycost = new Field(true);
        $oOrderOverview->oxorder__oxpayvat = new Field(true);

        $orderOverviewObj = new OrderOverview($oOrderOverview, null);
        $result = $orderOverviewObj->getPayCost($oOrderOverview);
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
     * Test case to fetch Charge Id from database table oxorder
     */

    public function testGetChargeId()
    {
        $response = $this->oOrderOverviewControllerTest->getNetsPaymentResponce();
        $oCommonHelper = $this->getMockBuilder(CommonHelper::class)->setMethods(['getCurlResponse', 'getPaymentId', 'getApiUrl'])->getMock();
        $oCommonHelper->expects($this->any())->method('getCurlResponse')->willReturn($response);
        $oCommonHelper->expects($this->any())->method('getPaymentId')->willReturn(true);
        $oCommonHelper->expects($this->any())->method('getApiUrl')->willReturn('url');

        $orderOverviewObj = new OrderOverview(null, $oCommonHelper);
        $result = $orderOverviewObj->getChargeId(100);
        $this->assertNotEmpty($result);
    }

    /*
     * Test case to prepare items for refund
     */

    public function testGetItemForRefund()
    {
        $data = array(
            "items" => array(0 => ['reference' => 'fdaqwefffq1wd2',
                    'quantity' => 100,
                    'oxbprice' => 100,
                    'taxRate' => 100,
                    'netTotalAmount' => 100,
                    'grossTotalAmount' => 100,
                    'taxAmount' => 100,
                    'oxbprice' => 100
                ]),
            "totalAmt" => 100
        );
        $items = $this->oOrderOverviewObject->getItemForRefund('fdaqwefffq1wd2', 1, $data);
        $this->assertNotEmpty($items);
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
