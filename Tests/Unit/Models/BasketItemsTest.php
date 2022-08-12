<?php

namespace Es\NetsEasy\Tests\Unit\Models;

use OxidEsales\Eshop\Application\Model\Basket;
use \Es\NetsEasy\ShopExtend\Application\Models\BasketItems as NetsBasketItems;
use OxidEsales\Eshop\Core\Field;

class BasketItemsTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $basketItemsObject;


    protected function setUp(): void
    {
        parent::setUp();
        include_once dirname(__FILE__) . "/../../../../../../bootstrap.php";
        $this->basketItemsObject = \oxNew(NetsBasketItems::class);
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

        $result = $this->basketItemsObject->getProductItem($articleMockBuilder);
        $this->assertNotEmpty($result);
    }

    /**
     * Test case to get discount item array
     */
    public function testGetDiscountItem()
    {
        $result = $this->basketItemsObject->getDiscountItem(100, 100);
        $this->assertArrayHasKey('grossTotalAmount', $result[0]);
        $this->assertContains(100, $result[0]);
    }

    /**
     * Test case to get item list array
     */
    public function testGetItemList()
    {
        $oOrder = $this->getMockBuilder(NetsBasketItems::class)->setMethods(['getDiscountSum'])->getMock();
        $oOrder->expects($this->any())->method('getDiscountSum')->willReturn(100);

        $mockBuilder = $this->getMockBuilder(\OxidEsales\Eshop\Application\Model\Basket::class);
        $mockBuilder->setMethods(['getContents', 'getDeliveryCost', 'getBruttoPrice', 'getPaymentCost']);
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
        $basket->expects($this->any())->method('getBruttoPrice')->willReturn(100);
        $basket->expects($this->any())->method('getDeliveryCost')->will($this->returnValue($basket));

        $basket->expects($this->any())->method('getBruttoPrice')->willReturn(100);
        $basket->expects($this->any())->method('getPaymentCost')->will($this->returnValue($basket));

        $oOrdeObj = new NetsBasketItems($oOrder);
        $result = $oOrdeObj->getItemList($basket);
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


        $basket->expects($this->any())->method('getVouchers')->willReturn([0 => $vouchersObj]);

        $result = $this->basketItemsObject->getDiscountSum($basket);
        $this->assertNotEmpty($result);
    }

}
