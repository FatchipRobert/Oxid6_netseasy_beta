<?php

namespace Es\NetsEasy\Tests\Unit\Core;

use Es\NetsEasy\Core\DebugHandler as NetsDebugHandler;

class DebugHandlerTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $oNetsDebugHandler;

    protected function setUp(): void
    {
        parent::setUp();
        include_once dirname(__FILE__) . "/../../../../../../bootstrap.php";
        $this->oNetsDebugHandler = \oxNew(NetsDebugHandler::class);
    }

    /**
     * Test case to get headers
     */
    public function testLog()
    {
        $result = $this->oNetsDebugHandler->log('Unit testing on - '.date('Y-m-d'));
        $this->assertTrue($result);
    }

}
