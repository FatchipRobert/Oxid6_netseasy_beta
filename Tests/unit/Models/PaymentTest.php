<?php

namespace Es\NetsEasy\Tests\Unit\Models;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\User;
use \Es\NetsEasy\ShopExtend\Application\Models\payment as NetsPayment;
use Es\NetsEasy\Core\CommonHelper;
use OxidEsales\Eshop\Core\Registry;
use Es\NetsEasy\Tests\Unit\Models\OrderTest;
use \OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use \OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

class PaymentTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $paymentObject;
    protected $oOrderTest;
    protected $oxSession;

    protected function setUp(): void
    {
        parent::setUp();
        include_once dirname(__FILE__) . "/../../../../../../bootstrap.php";
        $this->paymentObject = \oxNew(NetsPayment::class, \oxNew(\Es\NetsEasy\Core\CommonHelper::class));
        $this->oOrderTest = \oxNew(OrderTest::class);
        $this->oxSession = \oxNew(\OxidEsales\EshopCommunity\Core\Session::class);
    }

    /**
     * Test case to get payment response
     */
    public function testGetPaymentResponse()
    {
        $mockBuilder = $this->getMockBuilder(\OxidEsales\Eshop\Core\Price::class);
        $mockBuilder->setMethods(['getBruttoPrice']);
        $price = $mockBuilder->getMock();
        $price->expects($this->any())->method("getBruttoPrice")->will($this->returnValue(129.00));

        $mockBuilder = $this->getMockBuilder(\OxidEsales\Eshop\Application\Model\Basket::class);
        $mockBuilder->setMethods(['getPrice']);
        $basket = $mockBuilder->getMock();
        $basket->expects($this->any())->method("getPrice")->will($this->returnValue($price));

        $datastring = $this->oOrderTest->getDatastring();
        $oCommonHelper = $this->getMockBuilder(CommonHelper::class)->setMethods(['getCurlResponse', 'getApiUrl'])->getMock();
        $oCommonHelper->expects($this->any())->method('getCurlResponse')->willReturn("{'paymentId':'testpaymentId'}");
        $oCommonHelper->expects($this->any())->method('getApiUrl')->willReturn('url');

        $utilMockBuilder = $this->getMockBuilder(Registry::class);
        $utilMockBuilder->setMethods(['redirect']);
        $utils = $utilMockBuilder->getMock();
        $utils->expects($this->any())->method('redirect')->willReturn('test');

        $oOrder = new NetsPayment($oCommonHelper, $utils);
        $result = $oOrder->getPaymentResponse($datastring, $basket, 100);
        $this->assertNull($result);
    }

    /**
     * Test case to get prepare datastring Params array
     */
    public function testPrepareDatastringParams()
    {
        $deliverAddrObj = new \stdClass;
        $deliverAddrObj->housenumber = 122;
        $deliverAddrObj->street = 'xys street';
        $deliverAddrObj->zip = 4122;
        $deliverAddrObj->city = 'Neyork';
        $deliverAddrObj->country = 'In';
        $deliverAddrObj->company = 'XZY';
        $deliverAddrObj->firstname = 'firstname';
        $deliverAddrObj->lastname = 'lastname';
        $daten = ['delivery_address' => $deliverAddrObj, 'email' => 'test@test.com'];
        $result = $this->paymentObject->prepareDatastringParams($daten, [], $paymentId = null);
        $this->assertNotEmpty($result);
        $deliverAddrObj->company = '';
        $oxConfig = \oxNew(\OxidEsales\EshopCommunity\Core\Config::class);
        $oxConfig->setConfigParam('nets_checkout_mode', true);
        $result = $this->paymentObject->prepareDatastringParams($daten, [], $paymentId = null);
    }

    /**
     * Test case to get Order Id of order
     */
    public function testGetOrderId()
    {
        $mockBuilder = $this->getMockBuilder(\OxidEsales\Eshop\Application\Model\Basket::class);
        $mockBuilder->setMethods(['getOrderId']);
        $basket = $mockBuilder->getMock();
        $basket->expects($this->any())->method("getOrderId")->willReturn(100);
        $this->oxSession->setBasket($basket);

        $result = $this->paymentObject->getOrderId();
        $this->assertNull($result);
    }

    /**
     * Test case for Order::savePaymentDetails()
     */
    public function testSavePaymentDetails()
    {
        $result = $this->paymentObject->savePaymentDetails(json_decode('{
            "payment":{
               "paymentId":"0126000062a745c1f24370d976ebd20e",
               "checkout":{
                  "url":"http://oxideshop.local:81/index.php?cl=thankyou"
               },
               "charges":[
                  {
                     "chargeId":"00ab000062a7462cf24370d976ebd21d",
                     "amount":32900,
                     "created":"2022-06-13T14:14:04.5570+00:00",
                     "orderItems":[
                        {
                           "reference":"2103",
                           "name":"Wakeboard GROOVE",
                           "quantity":1.0,
                           "unit":"pcs",
                           "unitPrice":27647,
                           "taxRate":1900,
                           "taxAmount":5253,
                           "grossTotalAmount":32900,
                           "netTotalAmount":27647
                        }
                     ]
                  }
               ]
            }
         }', true));
        $this->assertTrue($result);
    }

    /**
     * Function to get user id
     * @return string
     */
    public function getUserId()
    {
        $queryBuilder = ContainerFactory::getInstance()
                        ->getContainer()
                        ->get(QueryBuilderFactoryInterface::class)->create();
        return $queryBuilder
                        ->select('oxid')
                        ->from('oxuser')->execute()->fetchOne();
    }

}
