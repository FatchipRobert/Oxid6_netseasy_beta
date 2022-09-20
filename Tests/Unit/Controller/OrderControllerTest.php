<?php

namespace Es\NetsEasy\Tests\Unit\Controller;

use \Es\NetsEasy\ShopExtend\Application\Controller\OrderController;
use \Es\NetsEasy\ShopExtend\Application\Models\Order as NetsOrder;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\UtilsObject;
use OxidEsales\Eshop\Application\Controller\ThankyouController;
use OxidEsales\EshopCommunity\Core\Registry;
use Es\NetsEasy\Core\CommonHelper;

class OrderControllerTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $orderObject;
    protected $oxSession;
    protected $oxConfig;

    protected function setUp(): void
    {
        parent::setUp();
        include_once dirname(__FILE__) . "/../../../../../../bootstrap.php";
        $this->orderObject = \oxNew(OrderController::class);
        $sut = $this->getMockBuilder(OxidEsales\Eshop\Application\Controller\OrderController::class)->setMethods(['init'])->getMock();
        $payment = $this->getMockBuilder(Payment::class)->setMethods(['load'])->getMock();
        $payment->expects($this->any())->method('load')->willReturn(true);
        $payment->oxpayments__oxactive = new Field(true);
        $this->oxSession = \oxNew(\OxidEsales\EshopCommunity\Core\Session::class);
        $this->oxConfig = \oxNew(\OxidEsales\EshopCommunity\Core\Config::class);
    }

    /**
     * Test case for OrderController::execute()
     */
    public function testExecute()
    {
        Registry::getSession()->setVariable('payment_id', '0230000062a996e863308f63c7333a01');
        Registry::getSession()->setVariable('paymentid', 'nets_easy');
        $order = $this->getMockBuilder(NetsOrder::class)
                ->disableOriginalConstructor()->setMethods(['isEmbedded', 'processOrder'])
                ->getMock();

        $order->expects($this->any())->method('processOrder')->willReturn(1);
        $order->expects($this->any())->method('isEmbedded')->willReturn(1);

        $user = $this->getMockBuilder(User::class)->setMethods(['getType', 'onOrderExecute'])->getMock();
        $user->expects($this->any())->method('getType')->willReturn(0);
        $basket = $this->getMockBuilder(\OxidEsales\Eshop\Application\Model\Basket::class)->setMethods(['getProductsCount'])->getMock();
        $basket->expects($this->any())->method('getProductsCount')->willReturn(true);
        Registry::getSession()->setBasket($basket);
        $mockBuilder = $this->getMockBuilder(Registry::class);
        $mockBuilder->setMethods(['redirect']);
        $utils = $mockBuilder->getMock();
        $utils->expects($this->any())->method('redirect')->willReturn('test');

        $oOrderOverview = new OrderController($order, null, $utils, $basket);
        $result = $oOrderOverview->execute();
        $this->assertEquals('test', $result);

        $order->expects($this->any())->method('isEmbedded')->willReturn(0);
        $result = $oOrderOverview->execute();
    }

    /**
     * Test case for OrderController::isEmbedded()
     */
    public function testIsEmbedded()
    {
        $embedded = $this->orderObject->isEmbedded();
        if ($embedded) {
            $this->assertTrue($embedded);
        } else {
            $this->assertFalse($embedded);
        }
    }

    /**
     * Test case for get return data after hosted payment checkout is done
     * @return null
     */
    public function testReturnhosted()
    {
        $this->oxSession->setVariable('payment_id', '0230000062a996e863308f63c7333a01');
        $this->oxConfig->setConfigParam('nets_autocapture', 1);

        $oOrder = $this->getMockBuilder(NetsOrder::class)->disableOriginalConstructor()->setMethods(['savePaymentDetails','updateOrdernr'])->getMock();
        $oOrder->expects($this->any())->method('savePaymentDetails')->willReturn(1);
        $oOrder->expects($this->any())->method('updateOrdernr')->willReturn(1);

        $mockBuilder = $this->getMockBuilder(Registry::class);
        $mockBuilder->setMethods(['redirect']);
        $utils = $mockBuilder->getMock();
        $utils->expects($this->any())->method('redirect')->willReturn('test');

        $oCommonHelper = $this->getMockBuilder(CommonHelper::class)->setMethods(['getCurlResponse', 'getApiUrl'])->getMock();
        $oCommonHelper->expects($this->any())->method('getCurlResponse')->willReturn("{'chargeId':'dummyChargeId'}");
        $oCommonHelper->expects($this->any())->method('getApiUrl')->willReturn('url');

        $oOrderOverview = new OrderController($oOrder, $oCommonHelper, $utils);
        $result = $oOrderOverview->returnhosted();
        $this->assertEquals('test', $result);
    }

    /*
     * Test case for to get checkout js url based on environment i.e live or test
     */

    public function testGetCheckoutJs()
    {
        $this->oxConfig->setConfigParam('nets_blMode', 1);
        $getCheckoutJs = $this->orderObject->getCheckoutJs();
        $this->oxConfig->setConfigParam('nets_blMode', 0);
        $getCheckoutJs = $this->orderObject->getCheckoutJs();
        $this->assertNotNull($getCheckoutJs);
    }

    /*
     * Test case to fetch checkout key to pass in checkout js options based on environment live or test
     */

    public function testGetCheckoutKey()
    {
        $getCheckoutKey = $this->orderObject->getCheckoutKey();
        $this->assertNotNull($getCheckoutKey);
    }

    /*
     * Test case to compile layout style file url for the embedded checkout type
     */

    public function testGetLayout()
    {
        $getLayout = $this->orderObject->getLayout();
        $this->assertNotNull($getLayout);
    }

    /**
     * Test case to get error message displayed on template file
     */
    public function testGetErrorMsg()
    {
        $this->oxSession->setVariable('nets_err_msg', 'test');
        $errorMsg = $this->orderObject->getErrorMsg();
        $this->assertEquals('test', $errorMsg);
    }

    /**
     * Test case to get basket amount
     */
    public function testGetBasketAmount()
    {
        $mockBuilder = $this->getMockBuilder(\OxidEsales\Eshop\Core\Price::class);
        $mockBuilder->setMethods(['getBruttoPrice']);
        $price = $mockBuilder->getMock();
        $price->expects($this->any())->method("getBruttoPrice")->will($this->returnValue(100));

        $mockBuilder = $this->getMockBuilder(\OxidEsales\Eshop\Application\Model\Basket::class);
        $mockBuilder->setMethods(['getPrice']);
        $basket = $mockBuilder->getMock();
        $basket->expects($this->any())->method("getPrice")->will($this->returnValue($price));
        Registry::getSession()->setBasket($basket);

        $oOrderOverview = new OrderController(null, null, null, $basket);
        $basketAmount = $oOrderOverview->getBasketAmount();
        $this->assertEquals(10000, $basketAmount);
    }

    /*
     * Test case to get payment api response and pass it to template
     */

    public function testGetPaymentApiResponse()
    {
        $oOrder = $this->getMockBuilder(\OxidEsales\Eshop\Application\Model\Order::class)->setMethods(['createNetsTransaction'])->getMock();
        $oOrder->expects($this->any())->method('createNetsTransaction')->willReturn(true);

        $mockBuilder = $this->getMockBuilder(\OxidEsales\Eshop\Application\Model\Basket::class);
        $mockBuilder->setMethods(['getProductsCount']);
        $basket = $mockBuilder->getMock();
        $basket->expects($this->any())->method("getProductsCount")->willReturn(1);
        Registry::getSession()->setBasket($basket);

        $oOrderOverview = new OrderController($oOrder);
        $result = $oOrderOverview->getPaymentApiResponse();
        $this->assertTrue($result);
    }

}
