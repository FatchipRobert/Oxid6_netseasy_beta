<?php

namespace Es\NetsEasy\Core;

use Es\NetsEasy\Api\NetsLog;
use OxidEsales\EshopCommunity\Core\Registry;

/**
 * Class defines Nets order common helper functions
 */
class CommonHelper
{

    const RESPONSE_TYPE = "application/json";
    const ENDPOINT_TEST = 'https://test.api.dibspayment.eu/v1/payments/';
    const ENDPOINT_LIVE = 'https://api.dibspayment.eu/v1/payments/';
    const ENDPOINT_LIVE_CHARGES = 'https://api.dibspayment.eu/v1/charges/';
    const ENDPOINT_TEST_CHARGES = 'https://test.api.dibspayment.eu/v1/charges/';

    /*
     * Function to fetch secret key to pass as authorization
     * @return array
     */

    public function getHeaders($commercetag = false)
    {
        if ($commercetag) {
            return [
                "Content-Type: " . self::RESPONSE_TYPE,
                "Accept: " . self::RESPONSE_TYPE,
                "Authorization: " . self::getSecretKey(),
                "commercePlatformTag: " . "Oxid6"
            ];
        } else {
            return [
                "Content-Type: " . self::RESPONSE_TYPE,
                "Accept: " . self::RESPONSE_TYPE,
                "Authorization: " . self::getSecretKey()
            ];
        }
    }

    /*
     * Function to fetch secret key to pass as authorization
     * @return secret key
     */

    public function getSecretKey()
    {
        if (Registry::getConfig()->getConfigParam('nets_blMode') == 0) {
            return Registry::getConfig()->getConfigParam('nets_secret_key_test');
        } else {
            return Registry::getConfig()->getConfigParam('nets_secret_key_live');
        }
    }

    /*
     * Function to fetch payment id from database
     * @return payment id
     */

    public function getPaymentId($oxoder_id)
    {
        $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
        $sSQL_select = "SELECT transaction_id FROM oxnets WHERE oxorder_id = ? LIMIT 1";
        $payment_id = $oDB->getOne($sSQL_select, [
            $oxoder_id
        ]);
        return $payment_id;
    }

    /*
     * Function to get payment api url based on environment i.e live or test
     * return payment api url
     */

    public function getApiUrl()
    {
        if (Registry::getConfig()->getConfigParam('nets_blMode') == 0) {
            return self::ENDPOINT_TEST;
        } else {
            return self::ENDPOINT_LIVE;
        }
    }

    /*
     * Function to fetch charge api url
     * @param $paymentId
     * @return charge api url
     */

    public function getChargePaymentUrl(string $paymentId)
    {
        return (Registry::getConfig()->getConfigParam('nets_blMode') == 1) ? self::ENDPOINT_LIVE . $paymentId . '/charges' : self::ENDPOINT_TEST . $paymentId . '/charges';
    }

    /*
     * Function to fetch cancel api url
     * @param $paymentId
     * @return cancel api url
     */

    public function getVoidPaymentUrl(string $paymentId)
    {
        return (Registry::getConfig()->getConfigParam('nets_blMode') == 1) ? self::ENDPOINT_LIVE . $paymentId . '/cancels' : self::ENDPOINT_TEST . $paymentId . '/cancels';
    }

    /*
     * Function to fetch refund api url
     * @param $chargeId
     * @return refund api url
     */

    public function getRefundPaymentUrl($chargeId)
    {
        return (Registry::getConfig()->getConfigParam('nets_blMode') == 1) ? self::ENDPOINT_LIVE_CHARGES . $chargeId . '/refunds' : self::ENDPOINT_TEST_CHARGES . $chargeId . '/refunds';
    }

    /*
     * Function to compile layout style file url for the embedded checkout type
     * @return layout style
     */

    public function getLayout()
    {
        return Registry::getConfig()->getActiveView()
                        ->getViewConfig()
                        ->getModuleUrl("esnetseasy", "out/src/js/") . Registry::getConfig()->getConfigParam('nets_layout_mode') . '.js';
    }

    /*
     * Function to curl request to execute api calls
     * @return layout style
     */

    public function getCurlResponse($url, $method = "POST", $bodyParams = NULL)
    {
        $result = '';
        // initiating curl request to call api's
        $oCurl = curl_init();
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_HTTPHEADER, self::getHeaders());
        if ($method == "POST" || $method == "PUT") {
            curl_setopt($oCurl, CURLOPT_POSTFIELDS, $bodyParams);
        }
        $netsLog = \oxNew(NetsLog::class);
        $netsLog->log(true, "netsOrder Curl request headers," . json_encode(self::getHeaders()));
        $result = curl_exec($oCurl);
        $info = curl_getinfo($oCurl);
        switch ($info['http_code']) {
            case 401:
                $error_message = 'NETS Easy authorization filed. Check your secret/checkout keys';
                break;
            case 400:
                $error_message = 'NETS Easy Bad request: Please check request params/headers ';
                break;
            case 500:
                $error_message = 'Unexpected error';
                break;
        }
        if (!empty($error_message)) {
            $netsLog->log(true, "netsOrder Curl request error, $error_message");
        }
        curl_close($oCurl);

        return $result;
    }

    /*
     * Function to fetch checkout key to pass in checkout js options based on environment live or test
     * @return checkout key
     */

    public function getCheckoutKey()
    {
        if (Registry::getConfig()->getConfigParam('nets_blMode') == 0) {
            return Registry::getConfig()->getConfigParam('nets_checkout_key_test');
        } else {
            return Registry::getConfig()->getConfigParam('nets_checkout_key_live');
        }
    }

    /*
     * Function to get update reference api url based on environment i.e live or test
     * return update reference api url
     */

    public function getUpdateRefUrl($paymentId)
    {
        if (Registry::getConfig()->getConfigParam('nets_blMode') == 0) {
            return self::ENDPOINT_TEST . $paymentId . '/referenceinformation';
        } else {
            return self::ENDPOINT_LIVE . $paymentId . '/referenceinformation';
        }
    }

}
