<?php

namespace Es\NetsEasy\Tests\Unit\Models;

use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\User;
use \Es\NetsEasy\ShopExtend\Application\Models\Address as NetsAddress;
use OxidEsales\Eshop\Core\Field;

class AddressTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $addressObject;

    protected function setUp(): void
    {
        parent::setUp();
        include_once dirname(__FILE__) . "/../../../../../../bootstrap.php";
        $this->addressObject = \oxNew(NetsAddress::class);
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
        $result = $this->addressObject->getDeliveryAddress($oMockOrder, $mockDB, $oUser);
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
        $result = $this->addressObject->getDeliveryAddress($oMockOrder, $mockDB, $oUser);
        $this->assertObjectHasAttribute('firstname', $result);
    }

    /**
     * Test case to set language
     */
    public function testSetAddress()
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
        $result = $this->addressObject->setAddress($oUser, $sTranslation = null, $basket);
        $this->assertArrayHasKey('language', $result);
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
