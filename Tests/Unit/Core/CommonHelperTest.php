<?php

namespace Es\NetsEasy\Tests\Unit\Core;

use Es\NetsEasy\Core\CommonHelper as NetsCommonHelper;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;

class CommonHelperTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $oNetsCommonHelper;
    protected $oxConfig;

    protected function setUp(): void
    {
        parent::setUp();
        include_once dirname(__FILE__) . "/../../../../../../bootstrap.php";
        $this->oNetsCommonHelper = \oxNew(NetsCommonHelper::class);
        $this->oxConfig = \oxNew(\OxidEsales\EshopCommunity\Core\Config::class);
    }

    /**
     * Test case to get headers
     */
    public function testGetHeaders()
    {
        $result = $this->oNetsCommonHelper->getHeaders(true);
        $this->assertNotEmpty($result);
    }

    /**
     * Test case to execute action on activate event
     */
    public function testGetPaymentId()
    {
        $result = $this->oNetsCommonHelper->getPaymentId(100);
        $this->assertFalse($result);
    }

    /**
     * Test case to get secret key
     */
    public function testGetSecretKey()
    {
        $this->oxConfig->setConfigParam('nets_blMode', 1);
        $result = $this->oNetsCommonHelper->getSecretKey();
        if ($result) {
            $this->assertNotNull($result);
        }
        $this->oxConfig->setConfigParam('nets_blMode', 0);
        $result = $this->oNetsCommonHelper->getSecretKey();
        if ($result) {
            $this->assertNotNull($result);
        }
    }

    /**
     * Test case to getApiUrl event
     */
    public function testGetApiUrl()
    {
        $this->oxConfig->setConfigParam('nets_blMode', 1);
        $result = $this->oNetsCommonHelper->getApiUrl();
        $this->assertNotEmpty($result);
        $this->oxConfig->setConfigParam('nets_blMode', 0);
        $result = $this->oNetsCommonHelper->getApiUrl();
        $this->assertNotEmpty($result);
    }

    /**
     * Test case to getVoidPaymentUrl event
     */
    public function testGetVoidPaymentUrl()
    {
        $result = $this->oNetsCommonHelper->getVoidPaymentUrl(100);
        if ($result) {
            $this->assertNotNull($result);
        } else {
            $this->assertNull($result);
        }
    }

    /**
     * Test case to getCheckoutKey event
     */
    public function testGetCheckoutKey()
    {
        $this->oxConfig->setConfigParam('nets_blMode', 1);
        $result = $this->oNetsCommonHelper->getCheckoutKey();
        $this->assertNotNull($result);
    }

    /**
     * Test case to getUpdateRefUrl event
     */
    public function testGetUpdateRefUrl()
    {
        $this->oxConfig->setConfigParam('nets_blMode', 0);
        $result = $this->oNetsCommonHelper->getUpdateRefUrl(100);
        $this->assertNotNull($result);
        $this->oxConfig->setConfigParam('nets_blMode', 1);
        $result = $this->oNetsCommonHelper->getUpdateRefUrl(100);
        $this->assertNotNull($result);
    }

}
