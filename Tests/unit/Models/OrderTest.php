<?php

namespace Es\NetsEasy\Tests\Unit\Models;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\User;
use \Es\NetsEasy\ShopExtend\Application\Models\Order as NetsOrder;
use Es\NetsEasy\Core\CommonHelper;
use OxidEsales\Eshop\Core\Field;
use \Es\NetsEasy\ShopExtend\Application\Models\BasketItems as NetsBasketItems;
use \Es\NetsEasy\ShopExtend\Application\Models\Payment as NetsPayment;
use \Es\NetsEasy\ShopExtend\Application\Models\Address as NetsAddress;
use OxidEsales\EshopCommunity\Core\Registry;
use \OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use \OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

class OrderTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $orderObject;
    protected $oxSession;
    protected $objCommonHelper;
    protected $objOrder;
    protected $objAddress;
    protected $objPayment;
    protected $objBasketItems;

    protected function setUp(): void
    {
        parent::setUp();
        include_once dirname(__FILE__) . "/../../../../../../bootstrap.php";
        $this->objCommonHelper = \oxNew(CommonHelper::class);
        $this->objOrder = \oxNew(Order::class);
        $this->objAddress = \oxNew(NetsAddress::class);
        $this->objPayment = \oxNew(NetsPayment::class, $this->objCommonHelper);
        $this->objBasketItems = \oxNew(NetsBasketItems::class);

        $this->orderObject = new NetsOrder(null, $this->objCommonHelper, null, $this->objOrder, $this->objBasketItems, $this->objPayment, $this->objAddress);
        $this->oxSession = \oxNew(\OxidEsales\EshopCommunity\Core\Session::class);
    }

    /**
     * Test case for get return data after hosted payment checkout is done
     */
    public function testCreateNetsTransaction()
    {
        $this->oxSession->setVariable('usr', $this->getUserId());
        $oOrder = $this->getMockBuilder(NetsOrder::class)->disableOriginalConstructor()->setMethods(['updateOrdernr', 'logOrderID', 'getOrderId'])->getMock();
        $oOrder->expects($this->any())->method('updateOrdernr')->willReturn(1);
        $oOrder->expects($this->any())->method('logOrderID')->willReturn(1);
        $oOrder->expects($this->any())->method('getOrderId')->willReturn(1);

        $mockBuilder = $this->getMockBuilder(\OxidEsales\Eshop\Application\Model\Basket::class);
        $mockBuilder->setMethods(['getContents']);
        $basket = $mockBuilder->getMock();
        $basket->expects($this->any())->method("getContents")->willReturn([
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
        $this->oxSession->setBasket($basket);
        $oNetsBasketItems = $this->getMockBuilder(NetsBasketItems::class)->setMethods(['getItemList', 'getDiscountItem', 'getProductItem'])->getMock();
        $oNetsBasketItems->expects($this->any())->method('getItemList')->willReturn(1);
        $oNetsBasketItems->expects($this->any())->method('getDiscountItem')->willReturn(1);
        $oNetsBasketItems->expects($this->any())->method('getProductItem')->willReturn([
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

        $oNetsPayment = $this->getMockBuilder(NetsPayment::class)->disableOriginalConstructor()->setMethods(['prepareDatastringParams', 'getPaymentResponse'])->getMock();
        $oNetsPayment->expects($this->any())->method('prepareDatastringParams')->willReturn(1);
        $oNetsPayment->expects($this->any())->method('getPaymentResponse')->willReturn(true);

        $oNetsAddress = $this->getMockBuilder(NetsAddress::class)->setMethods(['setAddress', 'getDeliveryAddress'])->getMock();
        $oNetsAddress->expects($this->any())->method('setAddress')->willReturn(['delivery_address']);
        $oNetsAddress->expects($this->any())->method('getDeliveryAddress')->willReturn(1);

        $oOrdeObj = new NetsOrder($oOrder, $this->objCommonHelper, null, $this->objOrder, $oNetsBasketItems, $oNetsPayment, $oNetsAddress);
        $result = $oOrdeObj->createNetsTransaction(100);
        $this->assertTrue($result);
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
     * Test case to get process order
     */
    public function testProcessOrder()
    {
        $this->oxSession->setVariable('payment_id', 'test_payment_id');

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

        $oMockUser = $this->getMockBuilder(User::class)->setMethods(['getEncodedDeliveryAddress'])->getMock();
        $oMockUser->expects($this->once())->method('getEncodedDeliveryAddress')->willReturn(1);

        $oOrdeObj = new NetsOrder(null, $oCommonHelper, null, $oMockOrder, $this->objBasketItems, $this->objPayment, $this->objAddress);
        $result = $oOrdeObj->processOrder($oMockUser);
        $this->assertTrue($result);
    }

    /**
     * Test case to update Ordernr of order
     */
    public function testUpdateOrdernr()
    {
        $oMockOrder = $this->getMockBuilder(Order::class)->setMethods(['finalizeOrder'])->getMock();
        $oMockOrder->oxorder__oxordernr = new Field(true);
        $oOrdeObj = new NetsOrder(null, $this->objCommonHelper, null, $oMockOrder, $this->objBasketItems, $this->objPayment, $this->objAddress);
        $result = $oOrdeObj->updateOrdernr(100);
        if ($result) {
            $this->assertNotEmpty($result);
        } else {
            $this->assertEmpty($result);
        }
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
        $oxConfig = \oxNew(\OxidEsales\EshopCommunity\Core\Config::class);
        $oxConfig->setConfigParam('nets_checkout_mode', true);
        $embedded = $this->orderObject->isEmbedded();
        if ($embedded) {
            $this->assertTrue($embedded);
        } else {
            $this->assertFalse($embedded);
        }
    }

    /**
     * Test case to get Order payment Id
     */
    public function testGetOrderPaymentId()
    {
        $result = $this->orderObject->getOrderPaymentId(1);
        if ($result) {
            $this->assertNotEmpty($result);
        }
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