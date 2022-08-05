<?php

namespace Es\NetsEasy\Tests\Unit\Core;

use Es\NetsEasy\Core\Events as NetsEvent;
use OxidEsales\Eshop\Core\Field;

class EventsTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $oThankyouController;

    protected function setUp(): void
    {
        parent::setUp();
        include_once dirname(__FILE__) . "/../../../../../../bootstrap.php";
        $this->oThankyouController = \oxNew(NetsEvent::class);
    }

    /**
     * Test case to execute action on activate event
     */
    public function testOnActivate()
    {
        \oxRegistry::getSession()->setVariable('isEventUnitTest','yes');
        $result = NetsEvent::onActivate();
        $this->assertNull($result);
        \oxRegistry::getSession()->setVariable('isEventUnitTest','');
    }

    /**
     * Test case to deactivate event
     */
    public function testOnDeactivate()
    {
        \oxRegistry::getSession()->setVariable('isEventUnitTest','yes');
        $result = NetsEvent::onDeactivate();
        $this->assertNull($result);
        \oxRegistry::getSession()->setVariable('isEventUnitTest','');
    }

    /**
     * Test case to checkTableStructure event
     */
    public function testExecuteModuleMigrations()
    {
        \oxRegistry::getSession()->setVariable('isEventUnitTest','yes');
        $result = NetsEvent::executeModuleMigrations();
        $this->assertNull($result);
        \oxRegistry::getSession()->setVariable('isEventUnitTest','');
    }

     

}
