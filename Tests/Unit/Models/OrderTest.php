<?php

namespace Es\NetsEasy\Tests\Unit\Models;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\User;
use \Es\NetsEasy\extend\Application\Models\Order as NetsOrder;
use Es\NetsEasy\Api\NetsLog;
use Es\NetsEasy\Core\CommonHelper;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\UtilsObject;
use OxidEsales\Eshop\Core\Registry;

class OrderTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $orderObject;

    const ENDPOINT_TEST = 'https://test.api.dibspayment.eu/v1/payments/';
    const ENDPOINT_LIVE = 'https://api.dibspayment.eu/v1/payments/';

    protected function setUp(): void
    {
        parent::setUp();
        include_once dirname(__FILE__) . "/../../../../../../bootstrap.php";
        $this->orderObject = \oxNew(NetsOrder::class);
    }

    /**
     * Test case for get return data after hosted payment checkout is done
     */
    public function testCreateNetsTransaction()
    {
        \oxRegistry::getSession()->setVariable('usr', $this->getUserId());

        $oOrder = $this->getMockBuilder(NetsOrder::class)->setMethods(['updateOrdernr', 'logOrderID', 'getOrderId', 'setLanguage', 'getItemList', 'getDiscountItem', 'getProductItem', 'getDeliveryAddress', 'prepareDatastringParams', 'getPaymentResponse'])->getMock();
        $oOrder->expects($this->any())->method('updateOrdernr')->willReturn(1);
        $oOrder->expects($this->any())->method('logOrderID')->willReturn(1);
        $oOrder->expects($this->any())->method('getOrderId')->willReturn(1);
        $oOrder->expects($this->any())->method('setLanguage')->willReturn(array('delivery_address'));
        $oOrder->expects($this->any())->method('getProductItem')->willReturn(array(
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
        $oOrder->expects($this->any())->method('getItemList')->willReturn(1);
        $oOrder->expects($this->any())->method('getDiscountItem')->willReturn(1);
        $oOrder->expects($this->any())->method('getDeliveryAddress')->willReturn(1);
        $oOrder->expects($this->any())->method('prepareDatastringParams')->willReturn(1);
        $oOrder->expects($this->any())->method('getPaymentResponse')->willReturn(true);

        $mockBuilder = $this->getMockBuilder(\OxidEsales\Eshop\Application\Model\Basket::class);
        $mockBuilder->setMethods(['getContents']);
        $basket = $mockBuilder->getMock();
        $basket->expects($this->any())->method("getContents")->willReturn(array(
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
        \oxRegistry::getSession()->setBasket($basket);

        $oOrdeObj = new NetsOrder($oOrder, null, null);
        $result = $oOrdeObj->createNetsTransaction(100);
        $this->assertTrue($result);
    }

    /**
     * Test case to log Order ID
     */
    public function testLogOrderID()
    {
        $oOrder = $this->getMockBuilder(NetsOrder::class)->setMethods(['logOrderID'])->getMock();
        $oOrder->expects($this->any())->method('logOrderID')->willReturn(1);
        $oOrder->oxorder__oxordernr = new Field(true);
        \oxRegistry::getSession()->setVariable('sess_challenge', '0230000062a996e863308f63c7333a01');
        $oOrdeObj = new NetsOrder($oOrder, null, null);
        $result = $oOrdeObj->logOrderID($oOrder, null);
        $this->assertNull($result);

        $oOrdeObj = new NetsOrder($oOrder, null, $oOrder);
    }

    /**
     * Test case to log Order ID
     */
    public function testLogCatchErrors()
    {
        $e = new \Exception();
        $result = $this->orderObject->logCatchErrors($e);
        $this->assertNull($result);
    }

    /**
     * Test case to get product item
     */
    public function testGetProductItem()
    {
        $mockBuilder = $this->getMockBuilder(\OxidEsales\Eshop\Core\Price::class);
        $mockBuilder->setMethods(['getBruttoPrice', 'getVat']);
        $price = $mockBuilder->getMock();
        $price->expects($this->any())->method("getBruttoPrice")->will($this->returnValue(129.00));
        $price->expects($this->any())->method("getVat")->willReturn(100);

        $mockBuilder = $this->getMockBuilder(\OxidEsales\Eshop\Application\Model\Basket::class);
        $mockBuilder->setMethods(['getPrice']);
        $basket = $mockBuilder->getMock();
        $basket->expects($this->any())->method("getPrice")->will($this->returnValue($price));

        $articleMockBuilder = $this->getMockBuilder(\OxidEsales\Eshop\Application\Model\Article::class)->setMethods(['getArticle', 'getPrice', 'getAmount'])->getMock();

        $articleMockBuilder->expects($this->any())->method("getArticle")->will($this->returnValue($basket));
        $articleMockBuilder->getArticle()->oxarticles__oxartnum = new Field(true);
        $articleMockBuilder->getArticle()->oxarticles__oxtitle = new Field(true);
        $articleMockBuilder->expects($this->any())->method("getPrice")->will($this->returnValue($price));
        $articleMockBuilder->expects($this->any())->method("getAmount")->willReturn(100);

        $result = $this->orderObject->getProductItem($articleMockBuilder);
        $this->assertNotEmpty($result);
    }

    /**
     * Test case to set language
     */
    public function testSetLanguage()
    {
        $mockBuilder = $this->getMockBuilder(\OxidEsales\Eshop\Core\Price::class);
        $mockBuilder->setMethods(['getBruttoPrice']);
        $price = $mockBuilder->getMock();
        $price->expects($this->any())->method("getBruttoPrice")->will($this->returnValue(129.00));

        $mockBuilder = $this->getMockBuilder(\OxidEsales\Eshop\Application\Model\Basket::class);
        $mockBuilder->setMethods(['getPrice']);
        $basket = $mockBuilder->getMock();
        $basket->expects($this->any())->method("getPrice")->will($this->returnValue($price));

        $sUserID = $this->getUserId();
        $oUser = \oxNew("oxuser", "core");
        $oUser->Load($sUserID);
        $result = $this->orderObject->setLanguage($oUser, $sTranslation = null, $basket);				 
        $this->assertArrayHasKey('language', $result);         
       
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

        $datastring = $this->getDatastring();
        $oCommonHelper = $this->getMockBuilder(CommonHelper::class)->setMethods(['getCurlResponse', 'getApiUrl'])->getMock();
        $oCommonHelper->expects($this->any())->method('getCurlResponse')->willReturn("{'paymentId':'testpaymentId'}");
        $oCommonHelper->expects($this->any())->method('getApiUrl')->willReturn('url');

        $oOrder = new NetsOrder(null, $oCommonHelper, null);
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
        $result = $this->orderObject->prepareDatastringParams($daten, array(), $paymentId = null);
        $this->assertNotEmpty($result);
        $deliverAddrObj->company = '';
        \oxRegistry::getConfig()->setConfigParam('nets_checkout_mode', true);
        $result = $this->orderObject->prepareDatastringParams($daten, array(), $paymentId = null);
    }

    /**
     * Test case to get dDelivery address array
     */
    public function testGetDeliveryAddress()
    {
        $oMockOrder = $this->getMockBuilder(Order::class)->setMethods(['Load'])->getMock();

        $mockBuilder = $this->getMockBuilder(\OxidEsales\Eshop\Core\DatabaseProvider::class);
        $mockBuilder->setMethods(['getDb', 'getOne']);
        $mockDB = $mockBuilder->getMock();
        $oMockUser = $this->getMockBuilder(User::class)->setMethods(['Load'])->getMock();
        //$oMockUser->expects($this->any())->method('Load')->willReturn(2);
        $sUserID = $this->getUserId();
        $oUser = \oxNew("oxuser", "core");
        $oUser->Load($sUserID);
        $result = $this->orderObject->getDeliveryAddress($oMockOrder, $mockDB, $oUser);
        $this->assertObjectHasAttribute('firstname', $result);

        $oMockOrder = $this->getMockBuilder(Order::class)->setMethods(['Load', 'getDelAddressInfo'])->getMock();

        $oMockOrder->oxaddress__oxfname = new Field(true);
        $oMockOrder->oxaddress__oxlname = new Field(true);
        $oMockOrder->oxaddress__oxstreet = new Field(true);
        $oMockOrder->oxaddress__oxstreetnr = new Field(true);
        $oMockOrder->oxaddress__oxzip = new Field(true);
        $oMockOrder->oxaddress__oxcity = new Field(true);
        $oMockOrder->oxaddress__oxcountryid = new Field(true);
        $oMockOrder->oxaddress__oxcompany = new Field(true);

        $oMockOrder->expects($this->any())->method("getDelAddressInfo")->will($this->returnValue($oMockOrder));
        $result = $this->orderObject->getDeliveryAddress($oMockOrder, $mockDB, $oUser);
        $this->assertObjectHasAttribute('firstname', $result);
    }

    /**
     * Test case to get discount item array
     */
    public function testGetDiscountItem()
    {
        $result = $this->orderObject->getDiscountItem(100, 100);
        $this->assertArrayHasKey('grossTotalAmount', $result[0]);
        $this->assertContains(100, $result[0]);
    }

    /**
     * Test case to get item list array
     */
    public function testGetItemList()
    {
        $oOrder = $this->getMockBuilder(NetsOrder::class)->setMethods(['getDiscountSum'])->getMock();
        $oOrder->expects($this->any())->method('getDiscountSum')->willReturn(100);

        $mockBuilder = $this->getMockBuilder(\OxidEsales\Eshop\Application\Model\Basket::class);
        $mockBuilder->setMethods(['getContents', 'getDeliveryCost', 'getBruttoPrice', 'getPaymentCost']);
        $basket = $mockBuilder->getMock();
        $basket->expects($this->any())->method("getContents")->willReturn(array(
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
        $basket->expects($this->any())->method('getBruttoPrice')->willReturn(100);
        $basket->expects($this->any())->method('getDeliveryCost')->will($this->returnValue($basket));

        $basket->expects($this->any())->method('getBruttoPrice')->willReturn(100);
        $basket->expects($this->any())->method('getPaymentCost')->will($this->returnValue($basket));

        $oOrdeObj = new NetsOrder($oOrder, null, null);
        $result = $oOrdeObj->getItemList($basket);
        $this->assertNotEmpty($result);
    }

    /**
     * Test case to get process order
     */
    public function testProcessOrder()
    {
        \oxRegistry::getSession()->setVariable('payment_id', 'test_payment_id');

        $oMockOrder = $this->getMockBuilder(Order::class)->setMethods(['finalizeOrder'])->getMock();
        $oMockOrder->oxorder__oxordernr = new Field(true);
        $oMockOrder->expects($this->once())->method('finalizeOrder')->willReturn(1);

        $oCommonHelper = $this->getMockBuilder(CommonHelper::class)->setMethods(['getCurlResponse', 'getApiUrl', 'getUpdateRefUrl'])->getMock();
        $oCommonHelper->expects($this->any())->method('getCurlResponse')->willReturn('{
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
         }');
        $oCommonHelper->expects($this->any())->method('getApiUrl')->willReturn('url');
        $oCommonHelper->expects($this->any())->method('getUpdateRefUrl')->willReturn('url');
        $oOrdeObj = new NetsOrder(null, $oCommonHelper, null, $oMockOrder);
        $result = $oOrdeObj->processOrder(100);
        $this->assertTrue($result);
    }

    /**
     * Test case to update Ordernr of order
     */
    public function testUpdateOrdernr()
    {
        $oMockOrder = $this->getMockBuilder(Order::class)->setMethods(['finalizeOrder'])->getMock();
        $oMockOrder->oxorder__oxordernr = new Field(true);
        //$oMockOrder->oxorder__oxordernr = 100;
        $oOrdeObj = new NetsOrder(null, null, null, $oMockOrder);
        $result = $oOrdeObj->updateOrdernr(100);
        $this->assertTrue($result);
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

        $result = $this->orderObject->getOrderId();
        $this->assertNotEmpty($result);
    }

    /**
     * Test case to get current order from basket
     */
    public function testGetDiscountSum()
    {
        $vouchersObj = new \stdClass;
        $vouchersObj->dVoucherdiscount = 122;
        $mockBuilder = $this->getMockBuilder(\OxidEsales\Eshop\Application\Model\Basket::class);
        $mockBuilder->setMethods(['getTotalDiscount', 'getBruttoPrice', 'getVouchers']);
        $basket = $mockBuilder->getMock();
        //$basket->expects($this->any())->method('getPaymentCosts')->willReturn(-100);
        $basket->expects($this->any())->method('getBruttoPrice')->willReturn(100);
        $basket->expects($this->any())->method('getTotalDiscount')->will($this->returnValue($basket));


        $basket->expects($this->any())->method('getVouchers')->willReturn(array(0 => $vouchersObj));

        $result = $this->orderObject->getDiscountSum($basket);
        $this->assertNotEmpty($result);
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
        \oxRegistry::getConfig()->setConfigParam('nets_checkout_mode', true);
        $embedded = $this->orderObject->isEmbedded();
        if ($embedded) {
            $this->assertTrue($embedded);
        } else {
            $this->assertFalse($embedded);
        }
    }

    /**
     * Test case for Order::savePaymentDetails()
     */
    public function testSavePaymentDetails()
    {
        $result = $this->orderObject->savePaymentDetails(json_decode('{
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

    /**
     * Function to get data string response
     * @return array
     */
    public function getDatastring()
    {
        return $datastring = '{
                        "order":{
                           "items":[
                              {
                                 "reference":"demo_6",
                                 "name":"The best is yet to come Framed poster",
                                 "quantity":2,
                                 "unit":"pcs",
                                 "unitPrice":2900,
                                 "taxRate":2500,
                                 "taxAmount":1450,
                                 "grossTotalAmount":7250,
                                 "netTotalAmount":5800
                              },
                              {
                                 "reference":"shipping",
                                 "name":"shipping",
                                 "quantity":1,
                                 "unit":"pcs",
                                 "unitPrice":1000,
                                 "taxRate":2000,
                                 "taxAmount":200,
                                 "grossTotalAmount":1200,
                                 "netTotalAmount":1000
                              },
                              {
                                 "reference":"discount",
                                 "name":"discount",
                                 "quantity":1,
                                 "unit":"pcs",
                                 "unitPrice":1000,
                                 "taxRate":2000,
                                 "taxAmount":-200,
                                 "grossTotalAmount":-1200,
                                 "netTotalAmount":-1000
                              }
                           ],
                           "amount":7250,
                           "currency":"DKK",
                           "reference":"ps_iCbEuzIsdVcW"
                        },
                        "checkout":{
                           "charge":"false",
                           "publicDevice":"false",
                           "integrationType":"HostedPaymentPage",
                           "returnUrl":"http:\/\/localhost:8081\/en\/module\/netseasy\/return?id_cart=23",
                           "cancelUrl":"http:\/\/localhost:8081\/en\/order",
                           "termsUrl":"http:\/\/localhost:8081\/en\/",
                           "merchantTermsUrl":"http:\/\/localhost:8081\/en\/",
                           "merchantHandlesConsumerData":true,
                           "consumerType":{
                              "default":"B2C",
                              "supportedTypes":[
                                 "B2C"
                              ]
                           }
                        },
                        "customer":{
                           "email":"test@shop.com",
                           "shippingAddress":{
                              "addressLine1":"1234567890",
                              "addressLine2":"",
                              "postalCode":"1234",
                              "city":"Test",
                              "country":"DK"
                           },
                           "company":{
                              "name":"test",
                              "contact":{
                                 "firstName":"TEst",
                                 "lastName":"TEst"
                              }
                           },
                           "phoneNumber":{
                              "prefix":"+45",
                              "number":"12345678"
                           }
                        }
                     }';
    }

}
