<?php

namespace Es\NetsEasy\ShopExtend\Application\Models;

use Es\NetsEasy\Api\NetsLog;
use Es\NetsEasy\Core\CommonHelper;
use OxidEsales\EshopCommunity\Core\Registry;
use \OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use \OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use Es\NetsEasy\Core\DebugHandler;
use Es\NetsEasy\ShopExtend\Application\Models\PaymentGateway;

/**
 * Nets Payment class
 * @mixin Es\NetsEasy\ShopExtend\Application\Model\Payment
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
    protected $oCommonHelper;
    protected $oDebugHandler;
    protected $queryBuilder;
    protected $oxConfig;
    protected $oxSession;
    protected $oPaymentGateway;
    protected $oxUtils;

    /**
     * Constructor
     * @param object $oxUtils The OXID Utils model injected object
     * @param object $commonHelper The service file injected as object
     * @return Null
     */
    public function __construct(CommonHelper $commonHelper, $oxUtils = null)
    {
        $this->oDebugHandler = \oxNew(DebugHandler::class);
        $this->oPaymentGateway = \oxNew(PaymentGateway::class);
        $this->oxConfig = \oxNew(\OxidEsales\EshopCommunity\Core\Config::class);
        $this->oxSession = \oxNew(\OxidEsales\EshopCommunity\Core\Session::class);
        // works only if StaticHelper is not autoloaded yet!
        $this->oCommonHelper = $commonHelper;
        $this->integrationType = self::HOSTED;
        if ($this->oxConfig->getConfigParam('nets_checkout_mode') == 'embedded') {
            $this->integrationType = self::EMBEDDED;
        }
        if (!$oxUtils) {
            $this->oxUtils = Registry::getUtils();
        } else {
            $this->oxUtils = $oxUtils;
        }
        $this->queryBuilder = ContainerFactory::getInstance()
                ->getContainer()
                ->get(QueryBuilderFactoryInterface::class);
    }

    /**
     * Function to get payment response
     * @param array $data The API post data
     * @param object $oxBasket The BasketItems Model injected object
     * @param string $oID The OXID Order ID
     * @return string payment id
     */
    public function getPaymentResponse($data, $oBasket, $oID)
    {
        $modus = $this->oxConfig->getConfigParam('nets_blMode');
        if ($modus == 0) {
            $apiUrl = self::ENDPOINT_TEST;
        } else {
            $apiUrl = self::ENDPOINT_LIVE;
        }
        $this->oDebugHandler->log("NetsOrder, api request data here 2 : " . json_encode($data));
        $api_return = $this->oCommonHelper->getCurlResponse($apiUrl, 'POST', json_encode($data));
        $response = json_decode($api_return, true);
        if (isset($response['paymentId'])) {
            $this->oxSession->setVariable('payment_id', $response['paymentId']);
        }
        if (!isset($response['paymentId'])) {
            $response['paymentId'] = null;
        }
        $this->oDebugHandler->log("NetsOrder, api return data create trans: " . json_decode($api_return, true));
        // create entry in oxnets table for transaction
        $this->oPaymentGateway->createTransactionEntry(json_encode($data), $api_return, $this->getOrderId(), $response['paymentId'], $oID, intval(strval($oBasket->getPrice()->getBruttoPrice() * 100)));
        // Set language for hosted payment page
        $language = Registry::getLang()->getLanguageAbbr();
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
            $this->oxUtils->redirect($response["hostedPaymentPageUrl"] . "&language=$lang");
        }
        return $response['paymentId'];
    }

    /**
     * Function to get current order from basket
     * @return string
     */
    public function getOrderId()
    {
        $mySession = $this->oxSession;
        $oBasket = $mySession->getBasket();
        return $oBasket->getOrderId();
    }

    /**
     * Function to save payment details
     * @param array $api_ret The API request array
     * @param string $paymentId The NETS payment ID
     * @return boolean
     */
    public function savePaymentDetails($api_ret, $paymentId = null)
    {
        if (isset($api_ret)) {
            foreach ($api_ret['payment']['charges'] as $ky => $val) {
                foreach ($val['orderItems'] as $key => $value) {
                    if (isset($val['chargeId'])) {
                        $queryBuilder = $this->queryBuilder->create();
                        $queryBuilder->insert('oxnets')
                                ->values(
                                        array(
                                            'transaction_id' => '?',
                                            'charge_id' => '?',
                                            'product_ref' => '?',
                                            'charge_qty' => '?',
                                            'charge_left_qty' => '?'
                                        )
                                )
                                ->setParameter(0, $paymentId)
                                ->setParameter(1, $val['chargeId'])
                                ->setParameter(2, $value['reference'])
                                ->setParameter(3, $value['quantity'])
                                ->setParameter(4, $value['quantity']);
                        $queryData = $queryBuilder->execute();
                    }
                }
            }
        }
        return true;
    }

    /**
     * Function to prepare datastring params array
     * @param array $daten The NETS API request data array
     * @param array $data The NETS configuration data array
     * @param string $paymentId The NETS payment ID
     * @return array
     */
    public function prepareDatastringParams($daten, $data, $paymentId = null)
    {
        $delivery_address = $daten['delivery_address'];
        $data['checkout']['integrationType'] = $this->integrationType;
        if ($this->oxConfig->getConfigParam('nets_checkout_mode') == 'embedded') {
            $data['checkout']['url'] = urldecode($this->oxConfig->getShopUrl() . 'index.php?cl=order&fnc=execute&paymentid=' . $paymentId);
        } else {
            $data['checkout']['returnUrl'] = urldecode($this->oxConfig->getShopUrl() . 'index.php?cl=order&fnc=returnhosted&paymentid=' . $paymentId);
            $data['checkout']['cancelUrl'] = urldecode($this->oxConfig->getShopUrl() . 'index.php?cl=order');
        }
        // if autocapture is enabled in nets module settings, pass it to nets api
        if ($this->oxConfig->getConfigParam('nets_autocapture')) {
            $data['checkout']['charge'] = true;
        }
        $data['checkout']['termsUrl'] = $this->oxConfig->getConfigParam('nets_terms_url');
        $data['checkout']['merchantTermsUrl'] = $this->oxConfig->getConfigParam('nets_merchant_terms_url');
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

    /**
     * Function to get active payments
     * @return array
     */
    public function getActivePayments()
    {
        $queryBuilder = $this->queryBuilder->create();
        $queryBuilder
                ->select('OXID')
                ->from('oxpayments')
                ->where('oxactive = :oxactive')
                ->setParameters([
                    'oxactive' => 1,
        ]);
        return $queryBuilder->execute()->fetchAll();
    }

}
