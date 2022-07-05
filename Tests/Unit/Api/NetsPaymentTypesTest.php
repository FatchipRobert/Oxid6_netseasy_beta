<?php

namespace Es\NetsEasy\Tests\Unit\Core;

use Es\NetsEasy\Api\NetsPaymentTypes;

class NetsPaymentTypesTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $oNetsPaymentTypes;

    protected function setUp(): void
    {
        parent::setUp();
        include_once dirname(__FILE__) . "/../../../../../../bootstrap.php";
        $this->oNetsPaymentTypes = \oxNew(NetsPaymentTypes::class);
    }

    /**
     * Test case to get Nets Payment Description
     */
    public function testGetNetsPaymentDesc()
    {
        $result = $this->oNetsPaymentTypes->getNetsPaymentDesc(10000);
        $this->assertFalse($result);
    }

    /**
     * Test case to get payment short description
     */
    public function testGetNetsPaymentShortDesc()
    {
        $result = $this->oNetsPaymentTypes->getNetsPaymentShortDesc(10000);
        $this->assertFalse($result);
    }

}
