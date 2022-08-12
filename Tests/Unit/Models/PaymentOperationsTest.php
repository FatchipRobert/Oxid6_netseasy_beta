<?php

namespace Es\NetsEasy\Tests\Unit\Models;

use \Es\NetsEasy\ShopExtend\Application\Models\PaymentOperations;
use Es\NetsEasy\Core\CommonHelper;
use Es\NetsEasy\Tests\Unit\Controller\Admin\OrderOverviewControllerTest;

class PaymentOperationsTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $oPaymentOperations;
    protected $oOrderOverviewControllerTest;

    protected function setUp(): void
    {
        parent::setUp();
        include_once dirname(__FILE__) . "/../../../../../../bootstrap.php";
        $this->oPaymentOperations = \oxNew(PaymentOperations::class);
        $this->oOrderOverviewControllerTest = \oxNew(OrderOverviewControllerTest::class);
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

        $oOrderItems = $this->getMockBuilder(OrderItems::class)->setMethods(['getOrderItems'])->getMock();
        $oOrderItems->expects($this->any())->method('getOrderItems')->willReturn(['items' => [0 => [
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
        $oPaymentOperations = $this->getMockBuilder(PaymentOperations::class)->setMethods(['getValueItem'])->getMock();
        $oPaymentOperations->expects($this->any())->method('getValueItem')->willReturn([
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

        $oPaymentOperations = new PaymentOperations($oPaymentOperations, $oCommonHelper, $oOrderItems);
        $result = $oPaymentOperations->getOrderCharged(100);
        $this->assertTrue($result);
    }

    /*
     * Test case to get value item list for charge
     * return int
     */

    public function testGetValueItem()
    {
        $result = $this->oPaymentOperations->getValueItem(["cancelledAmount" => 100, "oxbprice" => 10, "taxRate" => 2000], 100);
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
        $response = ['response' => json_decode($response, true)];
        $oPaymentOperations = $this->getMockBuilder(PaymentOperations::class)->setMethods(['getOrderItems', 'getChargeId', 'getItemForRefund'])->getMock();
        $oPaymentOperations->expects($this->any())->method('getOrderItems')->willReturn(['items' => [0 => [
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
        $oPaymentOperations->expects($this->any())->method('getChargeId')->willReturn($response);
        $oPaymentOperations->expects($this->any())->method('getItemForRefund')->willReturn([
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

        $oPaymentOperations = new PaymentOperations($oPaymentOperations, $oCommonHelper);
        $result = $oPaymentOperations->getOrderRefund();
        $this->assertNull($result);
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

        $oPaymentOperations = new PaymentOperations(null, $oCommonHelper);
        $result = $oPaymentOperations->getChargeId(100);
        $this->assertNotEmpty($result);
    }

    /*
     * Test case to prepare items for refund
     */

    public function testGetItemForRefund()
    {
        $data = [
            "items" => [0 => ['reference' => 'fdaqwefffq1wd2',
                    'quantity' => 100,
                    'oxbprice' => 100,
                    'taxRate' => 100,
                    'netTotalAmount' => 100,
                    'grossTotalAmount' => 100,
                    'taxAmount' => 100,
                    'oxbprice' => 100
                ]],
            "totalAmt" => 100
        ];
        $items = $this->oPaymentOperations->getItemForRefund('fdaqwefffq1wd2', 1, $data);
        $this->assertNotEmpty($items);
    }

}
