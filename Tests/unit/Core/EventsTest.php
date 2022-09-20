<?php

namespace Es\NetsEasy\Tests\Unit\Core;

use Es\NetsEasy\Core\Events as NetsEvent;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\EshopCommunity\Core\Registry;

class EventsTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $oThankyouController;
    protected $oxSession;

    protected function setUp(): void
    {
        parent::setUp();
        include_once dirname(__FILE__) . "/../../../../../../bootstrap.php";
        $this->oThankyouController = \oxNew(NetsEvent::class);
        $this->oxSession = \oxNew(\OxidEsales\EshopCommunity\Core\Session::class);
    }

    /**
     * Test case to execute action on activate event
     */
    public function testOnActivate()
    {
        $this->oxSession->setVariable('isEventUnitTest', 'yes');
        $result = NetsEvent::onActivate();
        $this->assertNull($result);
        $this->oxSession->setVariable('isEventUnitTest', '');
    }

    /**
     * Test case to deactivate event
     */
    public function testOnDeactivate()
    {
        $this->oxSession->setVariable('isEventUnitTest', 'yes');
        $result = NetsEvent::onDeactivate();
        $this->assertNull($result);
        $this->oxSession->setVariable('isEventUnitTest', '');
    }

    /**
     * Test case to checkTableStructure event
     */
    public function testExecuteModuleMigrations()
    {
        $this->oxSession->setVariable('isEventUnitTest', 'yes');
        $result = NetsEvent::executeModuleMigrations();
        $this->assertNull($result);
        $this->oxSession->setVariable('isEventUnitTest', '');
    }

}
