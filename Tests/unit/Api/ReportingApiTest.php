<?php

namespace Es\NetsEasy\Tests\Unit\Core;

use Es\NetsEasy\Api\ReportingApi;

class ReportingApiTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $oNetsPaymentTypes;

    protected function setUp(): void
    {
        parent::setUp();
        include_once dirname(__FILE__) . "/../../../../../../bootstrap.php";
        $this->oReportingApi = \oxNew(ReportingApi::class);
    }

    /**
     * Test case to get Nets Payment Description
     */
    public function testShowPopup()
    {
        $result = $this->oReportingApi->showPopup();
        $this->assertNotEmpty($result);
    }

    /**
     * Test case to get payment short description
     */
    public function testCallReportingApi()
    {
        $result = $this->oReportingApi->callReportingApi();
        $this->assertNotEmpty($result);
    }

}
