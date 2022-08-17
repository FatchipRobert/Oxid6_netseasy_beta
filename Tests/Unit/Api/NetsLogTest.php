<?php

namespace Es\NetsEasy\Tests\Unit\Core;

use Es\NetsEasy\Api\NetsLog;
use OxidEsales\Eshop\Core\Field;
use Es\NetsEasy\Tests\Unit\Controller\Admin\OrderOverviewControllerTest;

class NetsLogTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $oNetsLog;

    protected function setUp(): void
    {
        parent::setUp();
        include_once dirname(__FILE__) . "/../../../../../../bootstrap.php";
        $this->oNetsLog = \oxNew(NetsLog::class);
    }

    /**
     * Test case to get log
     */
    public function testLog()
    {
        $result = $this->oNetsLog->log(true, "NetsOrderController, constructor");
        $this->assertTrue($result);
    }

    /**
     * Test case to Utf8 
     */
    public function testUtf8Ensure()
    {
        $this->oNetsResponse = \oxNew(\Es\NetsEasy\Tests\Unit\Controller\Admin\OrderOverviewControllerTest::class);
        $response = $this->oNetsResponse->getNetsPaymentResponce();
        $result = $this->oNetsLog->utf8_ensure(json_decode($response, true));
        $this->assertNotEmpty($result);
        $items = new \stdClass;
        $items->name = 'ABC';
        $items->amount = 100;
        $result = $this->oNetsLog->utf8_ensure($items);
        $this->assertNotEmpty($result);
    }

    /**
     * Test case to set transaction id in db
     */
    public function testSetTransactionId()
    {
        $result = $this->oNetsLog->setTransactionId(999999, 999999);
        $this->assertNull($result);
        $result = $this->oNetsLog->setTransactionId(null, null);
        $this->assertNull($result);
    }

}
