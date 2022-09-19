<?php

namespace Es\NetsEasy\Tests\Unit\Models;

use Es\NetsEasy\ShopExtend\Application\Models\PaymentStatus;
use Es\NetsEasy\Core\CommonHelper;
use Es\NetsEasy\Tests\Unit\Controller\Admin\OrderOverviewControllerTest;

class PaymentStatusTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $oPaymentStatus;
    protected $oOrderOverviewControllerTest;

    protected function setUp(): void
    {
        parent::setUp();
        include_once dirname(__FILE__) . "/../../../../../../bootstrap.php";
        $this->oPaymentStatus = \oxNew(PaymentStatus::class,null,\oxNew(\Es\NetsEasy\Core\CommonHelper::class));
        $this->oOrderOverviewControllerTest = \oxNew(OrderOverviewControllerTest::class);
    }

    /**
     * Test case to check the nets payment status and display in admin order list backend page
     */
    public function testGetEasyStatus()
    {
        $oPaymentStatus = $this->getMockBuilder(PaymentStatus::class)->disableOriginalConstructor()->setMethods(['getOrderItems', 'getPaymentStatus'])->getMock();
        $oPaymentStatus->expects($this->any())->method('getOrderItems')->willReturn([
            'totalAmt' => 100,
            'items' => 'items'
        ]);
        $oPaymentStatus->expects($this->any())->method('getPaymentStatus')->willReturn(['dbPayStatus' => true]);

        $oCommonHelper = $this->getMockBuilder(CommonHelper::class)->setMethods(['getCurlResponse', 'getVoidPaymentUrl', 'getPaymentId', 'getApiUrl'])->getMock();
        $oCommonHelper->expects($this->any())->method('getCurlResponse')->willReturn("{'chargeId':'dummyChargeId'}");
        $oCommonHelper->expects($this->any())->method('getVoidPaymentUrl')->willReturn('url');
        $oCommonHelper->expects($this->any())->method('getPaymentId')->willReturn(100);
        $oCommonHelper->expects($this->any())->method('getApiUrl')->willReturn('url');

        $paymentStatusObj = new PaymentStatus($oPaymentStatus, $oCommonHelper);
        $result = $paymentStatusObj->getEasyStatus(100);
        $this->assertNotEmpty($result);
        
        $oCommonHelper = $this->getMockBuilder(CommonHelper::class)->setMethods(['getCurlResponse', 'getVoidPaymentUrl', 'getPaymentId', 'getApiUrl'])->getMock();
        $oCommonHelper->expects($this->any())->method('getCurlResponse')->willReturn("{'chargeId':'dummyChargeId'}");
        $oCommonHelper->expects($this->any())->method('getVoidPaymentUrl')->willReturn('url');
        $oCommonHelper->expects($this->any())->method('getPaymentId')->willReturn(null);
        $oCommonHelper->expects($this->any())->method('getApiUrl')->willReturn('url');

        $paymentStatusObj = new PaymentStatus($oPaymentStatus, $oCommonHelper);
        $result = $paymentStatusObj->getEasyStatus(100);
        $this->assertNotEmpty($result);

        
    }

    /**
     * Test case to get payment status
     */
    public function testGetPaymentStatus()
    {
        $response = $this->oOrderOverviewControllerTest->getNetsPaymentResponce();
        $result = $this->oPaymentStatus->getPaymentStatus(json_decode($response, true), 100);
        if ($result) {
            $this->assertNotEmpty($result);
            $this->assertArrayHasKey('payStatus', $result);
        }
        $response = json_decode($response, true);
        $response['payment']['summary']['reservedAmount'] = 1233;
        $result = $this->oPaymentStatus->getPaymentStatus($response, 100);
        if ($result) {
            $this->assertNotEmpty($result);
            $this->assertArrayHasKey('payStatus', $result);
        }
    }

}
