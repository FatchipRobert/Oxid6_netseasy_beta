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
        $result = NetsEvent::onActivate();
        $this->assertNull($result);
    }

    /**
     * Test case to deactivate event
     */
    public function testOnDeactivate()
    {
        $result = NetsEvent::onDeactivate();
        $this->assertNull($result);
    }

    /**
     * Test case to checkTableStructure event
     */
    public function testCheckTableStructure()
    {
        $result = NetsEvent::checkTableStructure();
        $this->assertNull($result);
    }

    /**
     * Test case to checkTableStructure event
     */
    public function testCreateTableStructure()
    {
        $result = NetsEvent::createTableStructure('oxnets');
        $this->assertNull($result);
    }

}
