<?php

namespace Es\NetsEasy\Application\Helper;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ShopVersion;

class Api
{
    const API_LIVE = 'https://api.dibspayment.eu/';
    const API_TEST = 'https://test.api.dibspayment.eu/';

    const JS_ENDPOINT_LIVE = "https://checkout.dibspayment.eu/v1/checkout.js";
    const JS_ENDPOINT_TEST = "https://test.checkout.dibspayment.eu/v1/checkout.js";

    const REPORTING_API = 'https://ps17.sokoni.it/module/api/enquiry';

    const EMBEDDED = "EmbeddedCheckout";
    const HOSTED = "HostedPaymentPage";

    /**
     * @var Api
     */
    protected static $oInstance = null;

    /**
     * Create singleton instance of current helper class
     *
     * @return Api
     */
    public static function getInstance()
    {
        if (self::$oInstance === null) {
            self::$oInstance = oxNew(self::class);
        }
        return self::$oInstance;
    }

    /**
     * Function to fetch secret key to pass as authorization
     *
     * @return string
     */
    public function getSecretKey()
    {
        if (Registry::getConfig()->getConfigParam('nets_blMode') == 1) {
            return Registry::getConfig()->getConfigParam('nets_secret_key_live');
        }
        return Registry::getConfig()->getConfigParam('nets_secret_key_test');
    }

    /**
     * Returns checkout key
     *
     * @return string
     */
    public function getCheckoutKey()
    {
        if (Registry::getConfig()->getConfigParam('nets_blMode') == 1) {
            return Registry::getConfig()->getConfigParam('nets_checkout_key_live');
        }
        return Registry::getConfig()->getConfigParam('nets_checkout_key_test');
    }

    /**
     * Function to get payment api url based on environment i.e live or test
     *
     * @return string
     */
    public function getApiUrl()
    {
        if (Registry::getConfig()->getConfigParam('nets_blMode') == 1) {
            return self::API_LIVE;
        }
        return self::API_TEST;
    }

    /**
     * Returns checkout javascipt url
     *
     * @return string
     */
    public function getCheckoutJs()
    {
        if (Registry::getConfig()->getConfigParam('nets_blMode') == 1) {
            return self::JS_ENDPOINT_LIVE;
        }
        return self::JS_ENDPOINT_TEST;
    }

    /**
     * Returns integration type
     *
     * @return string
     */
    public function getIntegrationType()
    {
        if (Registry::getConfig()->getConfigParam('nets_checkout_mode') == 'embedded') {
            return self::EMBEDDED;
        }
        return self::HOSTED;
    }

    /**
     * Returns if embedded mode is configured
     *
     * @return bool
     */
    public function isEmbeddedMode()
    {
        return $this->getIntegrationType() === self::EMBEDDED;
    }

    /**
     * Returns if hosted mode is configured
     *
     * @return bool
     */
    public function isHostedMode()
    {
        return $this->getIntegrationType() === self::HOSTED;
    }

    /**
     * Returns reporting api url
     *
     * @return string
     */
    public function getReportingApiUrl()
    {
        return self::REPORTING_API;
    }

    /**
     * Formats price for API
     *
     * @param  float $dPrice
     * @return float
     */
    public function formatPrice($dPrice)
    {
        return round(round($dPrice, 2) * 100);
    }

    /**
     * Function to compile layout style file url for the embedded checkout type
     *
     * @return string
     */
    public function getLayout()
    {
        return Registry::getConfig()->getActiveView()->getViewConfig()->getModuleUrl("esnetseasy", "out/src/js/").'layout.js';
    }

    /**
     * Returns the oxid shop identifier
     *
     * @return string
     */
    public function getShopIdentifier()
    {
        return Registry::getConfig()->getActiveShop()->oxshops__oxedition->value."_".ShopVersion::getVersion();
    }
}
