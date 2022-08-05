<?php

namespace Es\NetsEasy\ShopExtend\Application\Models;

use Es\NetsEasy\Api\NetsLog;
use Es\NetsEasy\Api\NetsPaymentTypes;
use Es\NetsEasy\Core\CommonHelper;

/**
 * Nets basket class
 * @mixin Es\NetsEasy\ShopExtend\Application\Model\Order
 */
class Payment
{

    const EMBEDDED = "EmbeddedCheckout";
    const HOSTED = "HostedPaymentPage";
    const ENDPOINT_TEST = 'https://test.api.dibspayment.eu/v1/payments/';
    const ENDPOINT_LIVE = 'https://api.dibspayment.eu/v1/payments/';
    const JS_ENDPOINT_TEST = "https://test.checkout.dibspayment.eu/v1/checkout.js";
    const JS_ENDPOINT_LIVE = "https://checkout.dibspayment.eu/v1/checkout.js";
    const RESPONSE_TYPE = "application/json";
    const MODULE_NAME = "nets_easy";

    protected $integrationType;
    public $_NetsLog = true;
    protected $oCommonHelper;
    protected $netsLog;

    public function __construct($commonHelper = null)
    {
        $this->_NetsLog = true;
        $this->netsLog = \oxNew(NetsLog::class);
        // works only if StaticHelper is not autoloaded yet!
        if (!$commonHelper) {
            $this->oCommonHelper = \oxNew(CommonHelper::class);
        } else {
            $this->oCommonHelper = $commonHelper;
        }
        $this->integrationType = self::HOSTED;
        if (\oxRegistry::getConfig()->getConfigParam('nets_checkout_mode') == 'embedded') {
            $this->integrationType = self::EMBEDDED;
        }
    }

    /**
     * Function to get payment response
     * @return payment id
     */
    public function getPaymentResponse($data, $oBasket, $oID)
    {
        $modus = \oxRegistry::getConfig()->getConfigParam('nets_blMode');
        if ($modus == 0) {
            $apiUrl = self::ENDPOINT_TEST;
        } else {
            $apiUrl = self::ENDPOINT_LIVE;
        }
        $this->netsLog->log(true, "NetsOrder, api request data here 2 : ", json_encode($data));
        $api_return = $this->oCommonHelper->getCurlResponse($apiUrl, 'POST', json_encode($data));
        $response = json_decode($api_return, true);
        if (isset($response['paymentId'])) { 
            \oxRegistry::getSession()->setVariable('payment_id', $response['paymentId']);
        }
        if (!isset($response['paymentId'])) {
            $response['paymentId'] = null;
        }
        $this->netsLog->log($this->_NetsLog, "NetsOrder, api return data create trans: ", json_decode($api_return, true));
        // create entry in oxnets table for transaction
        $this->netsLog->createTransactionEntry(json_encode($data), $api_return, $this->getOrderId(), $response['paymentId'], $oID, intval(strval($oBasket->getPrice()->getBruttoPrice() * 100)));
        // Set language for hosted payment page
        $language = \oxRegistry::getLang()->getLanguageAbbr();
        if ($language == 'en') {
            $lang = 'en-GB';
        }
        if ($language == 'de') {
            $lang = 'de-DE';
        }
        if ($language == 'dk') {
            $lang = 'da-DK';
        }
        if ($language == 'se') {
            $lang = 'sv-SE';
        }
        if ($language == 'no') {
            $lang = 'nb-NO';
        }
        if ($language == 'fi') {
            $lang = 'fi-FI';
        }
        if ($language == 'pl') {
            $lang = 'pl-PL';
        }
        if ($language == 'nl') {
            $lang = 'nl-NL';
        }
        if ($language == 'fr') {
            $lang = 'fr-FR';
        }
        if ($language == 'es') {
            $lang = 'es-ES';
        }
        
        if ($this->integrationType == self::HOSTED) {
            \oxRegistry::getUtils()->redirect($response["hostedPaymentPageUrl"] . "&language=$lang");
        }
        return $response['paymentId'];
    }

    /**
     * Function to get current order from basket
     * @return array
     */
    public function getOrderId()
    {
        $mySession = \oxRegistry::getSession();
        $oBasket = $mySession->getBasket();
        return $oBasket->getOrderId();
    }

    /**
     * Function to save payment details
     * @return null
     */
    public function savePaymentDetails($api_ret, $paymentId = null)
    {
        if (isset($api_ret)) {
            foreach ($api_ret['payment']['charges'] as $ky => $val) {
                foreach ($val['orderItems'] as $key => $value) {
                    if (isset($val['chargeId'])) {
                        $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
                        $charge_query = "INSERT INTO `oxnets` (`transaction_id`, `charge_id`,  `product_ref`, `charge_qty`, `charge_left_qty`) " . "values ('" . $paymentId . "', '" . $val['chargeId'] . "', '" . $value['reference'] . "', '" . $value['quantity'] . "', '" . $value['quantity'] . "')";
                        $oDB->Execute($charge_query);
                    }
                }
            }
        }
        return true;
    }

    /**
     * Function to prepare datastring params array
     * @return array
     */
    public function prepareDatastringParams($daten, $data, $paymentId = null)
    {
        $delivery_address = $daten['delivery_address'];
        $data['checkout']['integrationType'] = $this->integrationType;
        if (\oxRegistry::getConfig()->getConfigParam('nets_checkout_mode') == 'embedded') {
            $data['checkout']['url'] = urldecode(\oxRegistry::getConfig()->getShopUrl() . 'index.php?cl=thankyou');
        } else {
            $data['checkout']['returnUrl'] = urldecode(\oxRegistry::getConfig()->getShopUrl() . 'index.php?cl=order&fnc=returnhosted&paymentid=' . $paymentId);
            $data['checkout']['cancelUrl'] = urldecode(\oxRegistry::getConfig()->getShopUrl() . 'index.php?cl=order');
        }
        // if autocapture is enabled in nets module settings, pass it to nets api
        if (\oxRegistry::getConfig()->getConfigParam('nets_autocapture')) {
            $data['checkout']['charge'] = true;
        }
        $data['checkout']['termsUrl'] = \oxRegistry::getConfig()->getConfigParam('nets_terms_url');
        $data['checkout']['merchantTermsUrl'] = \oxRegistry::getConfig()->getConfigParam('nets_merchant_terms_url');
        $data['checkout']['merchantHandlesConsumerData'] = true;
        $data['checkout']['consumer'] = [
            'email' => $daten['email'],
            'shippingAddress' => [
                'addressLine1' => $delivery_address->housenumber,
                'addressLine2' => $delivery_address->street,
                'postalCode' => $delivery_address->zip,
                'city' => $delivery_address->city,
                'country' => $delivery_address->country
            ]
        ];
        if (empty($delivery_address->company)) {
            $data['checkout']['consumer']['privatePerson'] = [
                'firstName' => $delivery_address->firstname,
                'lastName' => $delivery_address->lastname
            ];
        } else {
            $data['checkout']['consumer']['company'] = [
                'name' => $delivery_address->company,
                'contact' => [
                    'firstName' => $delivery_address->firstname,
                    'lastName' => $delivery_address->lastname
                ]
            ];
        }
        return $data;
    }

}
