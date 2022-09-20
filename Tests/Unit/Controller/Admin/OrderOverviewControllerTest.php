<?php

namespace Es\NetsEasy\Tests\Unit\Controller\Admin;

use \Es\NetsEasy\ShopExtend\Application\Controller\Admin\OrderOverviewController;
use Es\NetsEasy\Core\CommonHelper;
use Es\NetsEasy\ShopExtend\Application\Models\OrderOverview;
use Es\NetsEasy\ShopExtend\Application\Models\PaymentOperations;
use Es\NetsEasy\ShopExtend\Application\Models\PaymentStatus;
use Es\NetsEasy\ShopExtend\Application\Models\OrderItems;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Core\Request;

class OrderOverviewControllerTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $oOrderOverviewController;
    protected $oCommonHelper;

    protected function setUp(): void
    {
        parent::setUp();
        include_once dirname(__FILE__) . "/../../../../../../../bootstrap.php";
        $this->oOrderOverviewController = \oxNew(OrderOverviewController::class);
        $this->oCommonHelper = \oxNew(CommonHelper::class);
    }

    /**
     * Test case to check the nets payment status and display in admin order list backend page
     */
    public function testIsEasy()
    {
        $oOrderOverviewController = $this->getMockBuilder(OrderOverviewController::class)->setMethods(['getPaymentMethod'])->getMock();
        $oOrderOverviewController->expects($this->any())->method('getPaymentMethod')->willReturn('nets_easy');
        $oOrderOverview = $this->getMockBuilder(OrderOverview::class)->setMethods(['getPaymentMethod'])->getMock();
        $oOrderOverview->expects($this->any())->method('getPaymentMethod')->willReturn('nets_easy');

        $oPaymentStatus = $this->getMockBuilder(PaymentStatus::class)->disableOriginalConstructor()->setMethods(['getEasyStatus'])->getMock();
        $oPaymentStatus->expects($this->any())->method('getEasyStatus')->willReturn([
            'payStatus' => 'reserved',
            'langStatus' => 'en'
        ]);
        $oOrderOverview = new OrderOverviewController($oOrderOverviewController, $oOrderOverview, null, null, null, $oPaymentStatus);
        $result = $oOrderOverview->isEasy(100);
        $this->assertArrayHasKey('payStatus', $result);
        $this->assertNotEmpty($result['payStatus']);
    }

    /**
     * Test case to get pay language status
     */
    public function testGetPayLangStatus()
    {
        $oPaymentStatus = $this->getMockBuilder(PaymentStatus::class)->disableOriginalConstructor()->setMethods(['getPaymentStatus'])->getMock();
        $oPaymentStatus->expects($this->any())->method('getPaymentStatus')->willReturn(true);
        $oOrderOverview = new OrderOverviewController(null, null, null, null, null, $oPaymentStatus);
        $result = $oOrderOverview->getPayLangStatus(100, 100);
        $this->assertTrue($result);
    }

    /*
     * Test case to capture nets transaction - calls Charge API
     */

    public function testGetOrderCharged()
    {
        $oPaymentOperations = $this->getMockBuilder(PaymentOperations::class)->disableOriginalConstructor()->setMethods(['getOrderCharged'])->getMock();
        $oPaymentOperations->expects($this->any())->method('getOrderCharged')->willReturn(1);
        $mockBuilder = $this->getMockBuilder(Registry::class);
        $mockBuilder->setMethods(['redirect']);
        $utils = $mockBuilder->getMock();
        $utils->expects($this->any())->method('redirect')->willReturn('test');
        $_POST['stoken'] = 'klllkk';
        $_POST['force_admin_sid'] = 'klllkk';
        $oOrderOverview = new OrderOverviewController(null, null, null, $utils, null, null, $oPaymentOperations);
        $result = $oOrderOverview->getOrderCharged();
        $this->assertEquals('test', $result);
    }

    /*
     * Test case to capture nets transaction - calls Refund API
     */

    public function testGetOrderRefund()
    {
        $oPaymentOperations = $this->getMockBuilder(PaymentOperations::class)->disableOriginalConstructor()->setMethods(['getOrderRefund'])->getMock();
        $oPaymentOperations->expects($this->any())->method('getOrderRefund')->willReturn(1);
        $mockBuilder = $this->getMockBuilder(Registry::class);
        $mockBuilder->setMethods(['redirect']);
        $utils = $mockBuilder->getMock();
        $utils->expects($this->any())->method('redirect')->willReturn('test');
        $oOrderOverview = new OrderOverviewController(null, null, null, $utils, null, null, $oPaymentOperations);
        $result = $oOrderOverview->getOrderRefund();
        $this->assertEquals('test', $result);
    }

    /*
     * Test case to cancel nets transaction - calls Refund API
     */

    public function testGetOrderCancel()
    {
        $oOrderItems = $this->getMockBuilder(OrderItems::class)->disableOriginalConstructor()->setMethods(['getOrderItems'])->getMock();
        $oOrderItems->expects($this->any())->method('getOrderItems')->willReturn([
            'totalAmt' => '100',
            'items' => 'items'
        ]);
        $mockBuilder = $this->getMockBuilder(Registry::class);
        $mockBuilder->setMethods(['redirect']);
        $utils = $mockBuilder->getMock();
        $utils->expects($this->any())->method('redirect')->willReturn('tested');
        $oCommonHelper = $this->getMockBuilder(CommonHelper::class)->setMethods(['getCurlResponse', 'getVoidPaymentUrl', 'getPaymentId'])->getMock();
        $oCommonHelper->expects($this->any())->method('getCurlResponse')->willReturn("{'chargeId':'dummyChargeId'}");
        $oCommonHelper->expects($this->any())->method('getVoidPaymentUrl')->willReturn('url');
        $oCommonHelper->expects($this->any())->method('getPaymentId')->willReturn(true);
        $oOrderOverview = new OrderOverviewController(null, null, $oCommonHelper, $utils, $oOrderItems);
        $result = $oOrderOverview->getOrderCancel();
        $this->assertEquals('tested', $result);
    }

    /*
     * Test case to get order items to pass capture, refund, cancel api
     */

    public function testGetOrderItems()
    {
        $oOrderItems = $this->getMockBuilder(OrderItems::class)->disableOriginalConstructor()->setMethods(['getOrderItems'])->getMock();
        $oOrderItems->expects($this->any())->method('getOrderItems')->willReturn(true);
        $oOrderOverview = new OrderOverviewController(null, null, null, null, $oOrderItems);
        $result = $oOrderOverview->getOrderItems(100);
        $this->assertTrue($result);
    }

    /*
     * Test case to get list of partial charge/refund and reserved items list
     */

    public function testCheckPartialItems()
    {
        $oOrder = $this->getMockBuilder(OrderOverviewController::class)->setMethods(['getOrderItems', 'getChargedItems', 'getRefundedItems', 'getLists'])->getMock();
        $oOrder->expects($this->any())->method('getOrderItems')->willReturn(['items' => [0 => [
                    'reference' => 'reference_abc',
                    'name' => 'ABC PRODUCT',
                    'quantity' => 2,
                    'oxbprice' => 1200
        ]]]);
        $oOrder->expects($this->any())->method('getChargedItems')->willReturn(['reference_abc' => [
                'reference' => 'reference_abc',
                'name' => 'ABC PRODUCT',
                'quantity' => 2,
                'price' => 1200
        ]]);

        $oOrder->expects($this->any())->method('getLists')->willReturn('tested');

        $oOrder->expects($this->any())->method('getRefundedItems')->willReturn(['items' => [0 => [
                    'reference' => 'reference_abc',
                    'name' => 'ABC PRODUCT',
                    'quantity' => 1,
                    'oxbprice' => 1200
        ]]]);
        $chargedArr = ['reference_abc' => [
                'reference' => 'reference_abc',
                'name' => 'ABC PRODUCT',
                'quantity' => 2,
                'price' => 1200
        ]];

        $oCommonHelper = $this->getMockBuilder(CommonHelper::class)->setMethods(['getCurlResponse', 'getApiUrl', 'getPaymentId'])->getMock();
        $oCommonHelper->expects($this->any())->method('getCurlResponse')->willReturn('                
            {"payment":{      
                "refunds":[
                   {
                      "refundId":"016d000062a74644f24370d976ebd220"            
                   }
                ],
                "charges":[
                   {
                      "chargeId":"00ab000062a7462cf24370d976ebd21d"

                   }
                ]
             }
          }');
        $oCommonHelper->expects($this->any())->method('getApiUrl')->willReturn('url');
        $oCommonHelper->expects($this->any())->method('getPaymentId')->willReturn(true);

        $oOrderOverview = new OrderOverviewController($oOrder, null, $oCommonHelper, null);
        $result = $oOrderOverview->checkPartialItems(100);
        $this->assertEquals('tested', $result);
    }

    /*
     * Test case to get List of items to pass to frontend for charged, refunded items
     */

    public function testGetLists()
    {
        $response = $this->getNetsPaymentResponce();
        $item = [
            "reference" => "2103",
            "name" => "Wakeboard GROOVE",
            "quantity" => 1.0,
            "unit" => "pcs",
            "unitPrice" => 27647,
            "taxRate" => 1900,
            "taxAmount" => 5253,
            "grossTotalAmount" => 32900,
            "netTotalAmount" => 27647
        ];
        $result = $this->oOrderOverviewController->getLists(json_decode($response, true), $item, $item, $item);
        if ($result) {
            $this->assertNotEmpty($result);
            $this->assertArrayHasKey('refundedItems', $result);
        }

        $response = json_decode($response, true);
        $response['payment']['summary']['reservedAmount'] = 1233;
        $result = $this->oOrderOverviewController->getLists($response, $item, $item, $item);
        if ($result) {
            $this->assertNotEmpty($result);
        }
    }

    /*
     * Test case to Fetch partial amount
     */

    public function testGetPartial()
    {
        $result = $this->oOrderOverviewController->getPartial(100);
        if ($result) {
            $this->assertNotEmpty($result);
        } else {
            $this->assertEmpty($result);
        }
    }

    /*
     * Test case to enable debug mode
     */

    public function testDebugMode()
    {
        $result = $this->oOrderOverviewController->debugMode();
        if ($result) {
            $this->assertTrue($result);
        } else {
            $this->assertFalse($result);
        }
    }

    /*
     * Test case to fetch payment method type from databse table oxorder
     */

    public function testGetPaymentMethod()
    {
        $oOrderOverview = $this->getMockBuilder(OrderOverview::class)->setMethods(['getPaymentMethod'])->getMock();
        $oOrderOverview->expects($this->any())->method('getPaymentMethod')->willReturn(true);
        $oOrderOverviewController = new OrderOverviewController(null, $oOrderOverview, null, null);
        $result = $oOrderOverviewController->getPaymentMethod(100);
        $this->assertTrue($result);
    }

    /*
     * Test case to get response
     */

    public function testGetResponse()
    {
        $oCommonHelper = $this->getMockBuilder(CommonHelper::class)->setMethods(['getCurlResponse', 'getApiUrl', 'getPaymentId'])->getMock();
        $oCommonHelper->expects($this->any())->method('getCurlResponse')->willReturn("{'chargeId':'dummyChargeId'}");
        $oCommonHelper->expects($this->any())->method('getApiUrl')->willReturn('url');
        $oCommonHelper->expects($this->any())->method('getPaymentId')->willReturn(true);

        $oOrderOverview = new OrderOverviewController(null, null, $oCommonHelper);
        $result = $oOrderOverview->getResponse(100);
        $this->assertNotEmpty($result);
    }

    /*
     * Test case to fetch payment api url
     */

    public function testGetApiUrl()
    {
        $result = $this->oOrderOverviewController->getApiUrl();
        if ($result) {
            $this->assertStringStartsWith('http', $result);
            $this->assertNotNull($result);
        }
        $oxConfig = \oxNew(\OxidEsales\EshopCommunity\Core\Config::class);
        $oxConfig->setConfigParam('nets_blMode', 1);
        $result = $this->oOrderOverviewController->getApiUrl();
        $this->assertNotEmpty($result);
    }

    /*
     * Test case to get charged items list
     */

    public function testGetChargedItems()
    {
        $response = $this->getNetsPaymentResponce();
        $result = $this->oOrderOverviewController->getChargedItems(json_decode($response, true));
        if ($result) {
            $this->assertNotEmpty($result);
        }
    }

    /*
     * Test case to get refunded items list
     */

    public function testGetRefundedItems()
    {
        $response = $this->getNetsPaymentResponce();
        $result = $this->oOrderOverviewController->getRefundedItems(json_decode($response, true));
        if ($result) {
            $this->assertNotEmpty($result);
        }
    }

    /**
     * Test case to get payment id from database to display in thank you page.
     */
    public function testGetPaymentId()
    {
        $oCommonHelper = $this->getMockBuilder(CommonHelper::class)->setMethods(['getPaymentId'])->getMock();
        $oCommonHelper->expects($this->any())->method('getPaymentId')->willReturn(true);

        $oOrderOverviewController = new OrderOverviewController(null, null, $oCommonHelper, null);
        $result = $oOrderOverviewController->getPaymentId(100);
        $this->assertTrue($result);
    }

    /**
     * Function to get nets order id
     * @return string
     */
    public function getNetsOrderId()
    {
        $queryBuilder = ContainerFactory::getInstance()
                        ->getContainer()
                        ->get(QueryBuilderFactoryInterface::class)->create();
        $queryBuilder
                ->select('oxid')
                ->from('oxorder')
                ->where('OXPAYMENTTYPE = :OXPAYMENT_TYPE')
                ->setParameters([
                    'OXPAYMENT_TYPE' => 'nets_easy',
        ]);
        return $queryBuilder->execute()->fetchOne();
    }

    /**
     * Function to set nets Api response
     * @return json
     */
    public function getNetsPaymentResponce()
    {
        return '{
                "payment":{
                   "paymentId":"0126000062a745c1f24370d976ebd20e",
                   "summary":{
                      "reservedAmount":98700,
                      "chargedAmount":98700
                   },
                   "consumer":{
                      "shippingAddress":{
                         "addressLine1":"423",
                         "addressLine2":"MG road, camp",
                         "receiverLine":"test user",
                         "postalCode":"3456",
                         "city":"den",
                         "country":"DNK"
                      },
                      "company":{
                         "contactDetails":{
                            "phoneNumber":{

                            }
                         }
                      },
                      "privatePerson":{
                         "firstName":"test",
                         "lastName":"user",
                         "email":"test@test.com",
                         "phoneNumber":{

                         }
                      },
                      "billingAddress":{
                         "addressLine1":"423",
                         "addressLine2":"MG road, camp",
                         "receiverLine":"test user",
                         "postalCode":"3456",
                         "city":"den",
                         "country":"DNK"
                      }
                   },
                   "paymentDetails":{
                      "paymentType":"CARD",
                      "paymentMethod":"MasterCard",
                      "invoiceDetails":{

                      },
                      "cardDetails":{
                         "maskedPan":"554433******0235",
                         "expiryDate":"1234"
                      }
                   },
                   "orderDetails":{
                      "amount":98700,
                      "currency":"EUR",
                      "reference":"30"
                   },
                   "checkout":{
                      "url":"http://oxideshop.local:81/index.php?cl=thankyou"
                   },
                   "created":"2022-06-13T14:12:52.5885+00:00",
                   "refunds":[
                      {
                         "refundId":"016d000062a74644f24370d976ebd220",
                         "amount":32900,
                         "state":"Pending",
                         "lastUpdated":"2022-06-13T14:14:28.3685+00:00",
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
                      },
                      {
                         "refundId":"00a9000062a74644f24370d976ebd221",
                         "amount":32900,
                         "state":"Pending",
                         "lastUpdated":"2022-06-13T14:14:28.4918+00:00",
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
                      },
                      {
                         "refundId":"0190000062a74749f24370d976ebd259",
                         "amount":32900,
                         "state":"Pending",
                         "lastUpdated":"2022-06-13T14:18:49.7281+00:00",
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
                   ],
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
                      },
                      {
                         "chargeId":"01c9000062a74636f24370d976ebd21e",
                         "amount":65800,
                         "created":"2022-06-13T14:14:14.7471+00:00",
                         "orderItems":[
                            {
                               "reference":"2103",
                               "name":"Wakeboard GROOVE",
                               "quantity":2.0,
                               "unit":"pcs",
                               "unitPrice":27647,
                               "taxRate":1900,
                               "taxAmount":10506,
                               "grossTotalAmount":65800,
                               "netTotalAmount":55294
                            }
                         ]
                      }
                   ]
                }
             }';
    }

}
