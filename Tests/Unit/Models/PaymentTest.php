<?php

namespace Es\NetsEasy\Tests\Unit\Models;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\User;
use \Es\NetsEasy\ShopExtend\Application\Models\payment as NetsPayment;
use Es\NetsEasy\Core\CommonHelper;
use OxidEsales\Eshop\Core\Registry;
use Es\NetsEasy\Tests\Unit\Models\OrderTest;
class PaymentTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $paymentObject;
    protected  $oOrderTest;


    protected function setUp(): void
    {
        parent::setUp();
        include_once dirname(__FILE__) . "/../../../../../../bootstrap.php";
        $this->paymentObject = \oxNew(NetsPayment::class);
        $this->oOrderTest = \oxNew(OrderTest::class);
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

        $oOrder = new NetsPayment($oCommonHelper);
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
        $daten = array('delivery_address' => $deliverAddrObj, 'email' => 'test@test.com');
        $result = $this->paymentObject->prepareDatastringParams($daten, array(), $paymentId = null);
        $this->assertNotEmpty($result);
        $deliverAddrObj->company = '';
        \oxRegistry::getConfig()->setConfigParam('nets_checkout_mode', true);
        $result = $this->paymentObject->prepareDatastringParams($daten, array(), $paymentId = null);
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

        \oxRegistry::getSession()->setBasket($basket);

        $result = $this->paymentObject->getOrderId();
        $this->assertNotEmpty($result);
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
        $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
        $sSQL_select = "SELECT oxid FROM oxuser LIMIT 1";
        return $oDB->getOne($sSQL_select);
    }

    

}
