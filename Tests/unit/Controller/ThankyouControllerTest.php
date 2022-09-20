<?php

namespace Es\NetsEasy\Tests\Unit\Controller;

use \Es\NetsEasy\ShopExtend\Application\Controller\ThankyouController as NetsThankYouController;
use OxidEsales\Eshop\Core\Field;

class ThankyouControllerTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $oThankyouController;

    protected function setUp(): void
    {
        parent::setUp();
        include_once dirname(__FILE__) . "/../../../../../../bootstrap.php";
        $this->oThankyouController = \oxNew(NetsThankYouController::class);
    }

    /**
     * Test case to get payment id from database to display in thank you page.
     */
    public function testGetPaymentId()
    {
        $oOrder = $this->getMockBuilder(OxidEsales\EshopCommunity\Application\Controller\ThankYouController::class)->setMethods(['getOrder'])->getMock();
        $oOrder->oxorder__oxid = new Field('1234');
        $oOrder->expects($this->any())->method('getOrder')->willReturn($oOrder);
        $oThankYouController = new NetsThankYouController();
        $result = $oThankYouController->getPaymentId($oOrder);
        $this->assertFalse($result);
    }

}
