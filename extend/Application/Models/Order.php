<?php

namespace Es\NetsEasy\extend\Application\Models;

use Es\NetsEasy\Api\NetsLog;
use Es\NetsEasy\Api\NetsPaymentTypes;
use Es\NetsEasy\Core\CommonHelper;

/**
 * Nets oxOrder class
 * @mixin Es\NetsEasy\extend\Application\Model\Order
 */
class Order
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
    protected $oOrder;
    protected $oxUtils;
    protected $oxOrder;

    public function __construct($oOrder = null, $commonHelper = null, $oxUtils = null, $oxOrder = null)
    {
        $this->_NetsLog = true;
        if (!$oOrder) {
            $this->oOrder = $this;
        } else {
            $this->oOrder = $oOrder;
        }
        // works only if StaticHelper is not autoloaded yet!
        if (!$commonHelper) {
            $this->oCommonHelper = \oxNew(CommonHelper::class);
        } else {
            $this->oCommonHelper = $commonHelper;
        }
        if (!$oxUtils) {
            $this->oxUtils = \oxRegistry::getUtils();
        } else {
            $this->oxUtils = $oxUtils;
        }
        if (!$oxOrder) {
            $this->oxOrder = \oxNew(\OxidEsales\Eshop\Application\Model\Order::class);
        } else {
            $this->oxOrder = $oxOrder;
        }
    }

    /**
     * Function to create transaction and call nets payment Api
     * @param $oOrder
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    public function createNetsTransaction($oOrder)
    {
        $this->_NetsLog = true;
        \oxRegistry::getSession()->deleteVariable('nets_err_msg');
        NetsLog::log($this->_NetsLog, "NetsOrder createNetsTransaction");
        $items = [];
        $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $this->integrationType = self::HOSTED;
        $sUserID = \oxRegistry::getSession()->getVariable("usr");
        $oUser = \oxNew("oxuser", "core");
        $oUser->Load($sUserID);
        $sCountryId = $oUser->oxuser__oxcountryid->value;
        $mySession = \oxRegistry::getSession();
        $oBasket = $mySession->getBasket();
        $oID = $this->oOrder->updateOrdernr(\oxRegistry::getSession()
                        ->getVariable('sess_challenge'));
        $this->oOrder->logOrderID($oOrder, $oID);
        $oID = $this->oOrder->getOrderId();
        $daten = $this->oOrder->setLanguage($oUser, $sTranslation = '', $oBasket);
        $basketcontents = $oBasket->getContents();
        $this->oOrder->getItemList($oBasket);
        /* gift wrap and greeting card amount to be added in total amount */
        $wrappingCostAmt = $oBasket->getCosts('oxwrapping');
        $wrapCost = $greetCardAmt = $shipCostAmt = $payCostAmt = 0;
        if ($wrappingCostAmt) {
            $wrapCost = $oBasket->isCalculationModeNetto() ? $wrappingCostAmt->getNettoPrice() : $wrappingCostAmt->getBruttoPrice();
            $wrapCost = round(round($wrapCost, 2) * 100);
        }
        $greetingCardAmt = $oBasket->getCosts('oxgiftcard');
        if ($greetingCardAmt) {
            $greetCardAmt = $oBasket->isCalculationModeNetto() ? $greetingCardAmt->getNettoPrice() : $greetingCardAmt->getBruttoPrice();
            $greetCardAmt = round(round($greetCardAmt, 2) * 100);
        }
        $this->oOrder->getDiscountItem($wrapCost, $greetCardAmt);
        $sumAmt = 0;
        foreach ($basketcontents as $item) {
            $items[] = $itemArray = $this->oOrder->getProductItem($item);
            $sumAmt += $itemArray['grossTotalAmount'];
        }
        $sumAmt = $sumAmt + $wrapCost + $greetCardAmt + $shipCostAmt + $payCostAmt;
        $daten['delivery_address'] = $this->oOrder->getDeliveryAddress($oOrder, $oDB, $oUser);
        // create order to be passed to nets api
        $data = [
            'order' => [
                'items' => $items,
                'amount' => $sumAmt,
                'currency' => $oBasket->getBasketCurrency()->name,
                'reference' => $oID
            ]
        ];
        $data = $this->oOrder->prepareDatastringParams($daten, $data, $paymentId = null);
        try {
            return $this->oOrder->getPaymentResponse($data, $oBasket, $oID);
        } catch (Exception $e) {
            $this->oOrder->logCatchErrors($e);
            \oxRegistry::getUtils()->redirect(\oxRegistry::getConfig()
                            ->getSslShopUrl() . 'index.php?cl=netsorder');
        }
        return true;
    }

    /**
     * Function to log Order ID
     * @return null
     */
    public function logOrderID($oOrder, $oID)
    {
        NetsLog::log($this->_NetsLog, 'oID: ', $oOrder->oxorder__oxordernr->value);
        // if oID is empty, use session value
        if (empty($oID)) {
            $sGetChallenge = \oxRegistry::getSession()->getVariable('sess_challenge');
            $oID = $sGetChallenge;
            NetsLog::log($this->_NetsLog, "NetsOrder, get oID from Session: ", $oID);
        }
        NetsLog::log($this->_NetsLog, 'oID: ', $oID);
    }

    /**
     * Function to log catch errors
     * @return null
     */
    public function logCatchErrors($e)
    {
        $error_message = $e->getMessage();
        NetsLog::log($this->_NetsLog, "NetsOrder, api exception : ", $e->getMessage());
        NetsLog::log($this->_NetsLog, "NetsOrder, $error_message");
        if (empty($error_message)) {
            $error_message = 'Payment Api Parameter issue';
        }
        \oxRegistry::getSession()->setVariable('nets_err_msg', $error_message);
    }

    /**
     * Function to get product item
     * @return array
     */
    public function getProductItem($item)
    {
        $quantity = $item->getAmount();
        $prodPrice = $item->getArticle()
                ->getPrice(1)
                ->getBruttoPrice(); // product price incl. VAT in DB format
        $tax = $item->getPrice()->getVat(); // Tax rate in DB format
        $taxFormat = '1' . str_pad(number_format((float) $tax, 2, '.', ''), 5, '0', STR_PAD_LEFT);
        $unitPrice = round(round(($prodPrice * 100) / $taxFormat, 2) * 100);
        $netAmount = round($quantity * $unitPrice);
        $grossAmount = round($quantity * ($prodPrice * 100));
        return [
            'reference' => $item->getArticle()->oxarticles__oxartnum->value,
            'name' => $item->getArticle()->oxarticles__oxtitle->value,
            'quantity' => $quantity,
            'unit' => 'pcs',
            'unitPrice' => $unitPrice,
            'taxRate' => $item->getPrice()->getVat() * 100,
            'taxAmount' => $grossAmount - $netAmount,
            'grossTotalAmount' => $grossAmount,
            'netTotalAmount' => $netAmount
        ];
        //$sumAmt += $grossAmount;
    }

    /**
     * Function to set language
     * @return array
     */
    public function setLanguage($oUser, $sTranslation, $oBasket)
    {
        $oLang = \oxRegistry::getLang();
        $iLang = 0;
        $iLang = $oLang->getTplLanguage();
        if (!isset($iLang)) {
            $iLang = $oLang->getBaseLanguage();
        }
        try {
            $sTranslation = $oLang->translateString($oUser->oxuser__oxsal->value, $iLang, isAdmin());
        } catch (oxLanguageException $oEx) {
            // is thrown in debug mode and has to be caught here, as smarty hangs otherwise!
        }
        $daten['checkout_type'] = \oxRegistry::getConfig()->getConfigParam('nets_checkout_mode');
        $lang_abbr = $oLang->getLanguageAbbr($iLang);
        if (isset($lang_abbr) && $lang_abbr === 'en') {
            $daten['language'] = 'en_US';
        } else if (isset($lang_abbr) && $lang_abbr === 'de') {
            $daten['language'] = 'de_DE';
        }
        $daten['title'] = $sTranslation;
        $daten['name_affix'] = $oUser->oxuser__oxaddinfo->value;
        $daten['telephone'] = $oUser->oxuser__oxfon->value;
        $daten['dob'] = $oUser->oxuser__oxbirthdate->value;
        $daten['email'] = $oUser->oxuser__oxusername->value;
        $daten['amount'] = intval(strval($oBasket->getPrice()->getBruttoPrice() * 100));
        $daten['currency'] = $oBasket->getBasketCurrency()->name;
        return $daten;
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
        NetsLog::log(true, "NetsOrder, api request data here 2 : ", json_encode($data));
        $api_return = $this->oCommonHelper->getCurlResponse($apiUrl, 'POST', json_encode($data));
        $response = json_decode($api_return, true);
        if (!isset($response['paymentId'])) {
            $response['paymentId'] = null;
        }
        NetsLog::log($this->_NetsLog, "NetsOrder, api return data create trans: ", json_decode($api_return, true));
        // create entry in oxnets table for transaction
        NetsLog::createTransactionEntry(json_encode($data), $api_return, $this->oOrder->getOrderId(), $response['paymentId'], $oID, intval(strval($oBasket->getPrice()->getBruttoPrice() * 100)));
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
        if (isset($response['paymentId'])) {
            \oxRegistry::getSession()->setVariable('payment_id', $response['paymentId']);
        }
        if ($this->integrationType == self::HOSTED) {
            \oxRegistry::getUtils()->redirect($response["hostedPaymentPageUrl"] . "&language=$lang");
        }
        return $response['paymentId'];
    }

    /**
     * Function to prepare datastring params array
     * @return array
     */
    public function prepareDatastringParams($daten, $data, $paymentId = null)
    {
        $delivery_address = $daten['delivery_address'];
        if (\oxRegistry::getConfig()->getConfigParam('nets_checkout_mode') == 'embedded') {
            $this->integrationType = self::EMBEDDED;
        }
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

    /**
     * Function to get dDelivery address array
     * @return array
     */
    public function getDeliveryAddress($oOrder, $oDB, $oUser)
    {
        $oDelAd = $oOrder->getDelAddressInfo();
        if ($oDelAd) {
            $delivery_address = new \stdClass();
            $delivery_address->firstname = $oDelAd->oxaddress__oxfname->value;
            $delivery_address->lastname = $oDelAd->oxaddress__oxlname->value;
            $delivery_address->street = $oDelAd->oxaddress__oxstreet->value;
            $delivery_address->housenumber = $oDelAd->oxaddress__oxstreetnr->value;
            $delivery_address->zip = $oDelAd->oxaddress__oxzip->value;
            $delivery_address->city = $oDelAd->oxaddress__oxcity->value;
            $sDelCountry = $oDelAd->oxaddress__oxcountryid->value;
            $delivery_address->country = $oDB->getOne("SELECT oxisoalpha3 FROM oxcountry WHERE oxid = ?", [
                $sDelCountry
            ]);
            $delivery_address->company = $oDelAd->oxaddress__oxcompany->value;
            return $delivery_address;
        } else {
            $delivery_address = new \stdClass();
            $delivery_address->firstname = $oUser->oxuser__oxfname->value;
            $delivery_address->lastname = $oUser->oxuser__oxlname->value;
            $delivery_address->street = $oUser->oxuser__oxstreet->value;
            $delivery_address->housenumber = $oUser->oxuser__oxstreetnr->value;
            $delivery_address->zip = $oUser->oxuser__oxzip->value;
            $delivery_address->city = $oUser->oxuser__oxcity->value;
            $delivery_address->country = $oDB->getOne("SELECT oxisoalpha3 FROM oxcountry WHERE oxid = ?", [
                $oUser->oxuser__oxcountryid->value
            ]);
            $delivery_address->company = $oUser->oxuser__oxcompany->value;
            return $delivery_address;
        }
    }

    /**
     * Function to get discount item array
     * @return null
     */
    public function getDiscountItem($wrapCost, $greetCardAmt)
    {
        if ($wrapCost > 0) {
            $items[] = [
                'reference' => 'Gift Wrapping',
                'name' => 'Gift Wrapping',
                'quantity' => 1,
                'unit' => 'units',
                'unitPrice' => $wrapCost,
                'taxAmount' => 0,
                'grossTotalAmount' => $wrapCost,
                'netTotalAmount' => $wrapCost
            ];
        }
        if ($greetCardAmt > 0) {
            $items[] = [
                'reference' => 'Greeting Card',
                'name' => 'Greeting Card',
                'quantity' => 1,
                'unit' => 'units',
                'unitPrice' => $greetCardAmt,
                'taxAmount' => 0,
                'grossTotalAmount' => $greetCardAmt,
                'netTotalAmount' => $greetCardAmt
            ];
        }
        return $items;
    }

    /**
     * Function to get item list array
     * @return null
     */
    public function getItemList($oBasket)
    {
        $wrapCost = $greetCardAmt = $shippingCost = $payCost = 0;
        $shippingCost = $oBasket->getDeliveryCost();
        if ($shippingCost) {
            $shipCostAmt = $oBasket->isCalculationModeNetto() ? $shippingCost->getNettoPrice() : $shippingCost->getBruttoPrice();
        }
        if ($shipCostAmt > 0) {
            $shipCostAmt = round(round($shipCostAmt, 2) * 100);
            $items[] = [
                'reference' => 'shipping',
                'name' => 'shipping',
                'quantity' => 1,
                'unit' => 'units',
                'unitPrice' => $shipCostAmt,
                'taxAmount' => 0,
                'grossTotalAmount' => $shipCostAmt,
                'netTotalAmount' => $shipCostAmt
            ];
        }
        $payCost = $oBasket->getPaymentCost();
        if ($payCost) {
            $payCostAmt = $oBasket->isCalculationModeNetto() ? $payCost->getNettoPrice() : $payCost->getBruttoPrice();
        }
        if ($payCostAmt > 0) {
            $payCostAmt = round(round($payCostAmt, 2) * 100);
            $items[] = [
                'reference' => 'payment costs',
                'name' => 'payment costs',
                'quantity' => 1,
                'unit' => 'units',
                'unitPrice' => $payCostAmt,
                'taxAmount' => 0,
                'grossTotalAmount' => $payCostAmt,
                'netTotalAmount' => $payCostAmt
            ];
        }
        $discAmount = $this->oOrder->getDiscountSum($oBasket);
        if ($discAmount > 0) {
            $items[] = [
                'reference' => 'discount',
                'name' => 'discount',
                'quantity' => 1,
                'unit' => 'units',
                'unitPrice' => - $discAmount,
                'taxAmount' => 0,
                'grossTotalAmount' => - $discAmount,
                'netTotalAmount' => - $discAmount
            ];
        }
        return $items;
    }

    /**
     * Function to finalizing ordering process (validating, storing order into DB, executing payment, setting status 
     * @return null
     */
    public function processOrder($oUser)
    {
        $sess_id = \oxRegistry::getSession()->getVariable('sess_challenge');
        //$resultSet = \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->select($query);
        $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
        $sSQL_select = "SELECT oxorder_id FROM oxnets WHERE oxorder_id = ? LIMIT 1";
        $order_id = $oDB->getOne($sSQL_select, [
            $sess_id
        ]);
        if (!empty($order_id)) {
            $orderId = \OxidEsales\Eshop\Core\UtilsObject::getInstance()->generateUID();
            // $this->save();
            \OxidEsales\Eshop\Core\Registry::getSession()->setVariable("sess_challenge", $orderId);
        }
        // finalizing ordering process (validating, storing order into DB, executing payment, setting status ...)
        $oBasket = \oxRegistry::getSession()->getBasket();
        //$oOrder = \oxNew(\OxidEsales\Eshop\Application\Model\Order::class);
        $iSuccess = $this->oxOrder->finalizeOrder($oBasket, $oUser);
        $paymentId = \oxRegistry::getSession()->getVariable('payment_id');
        $orderNr = null;
        if (isset($this->oxOrder->oxorder__oxordernr->value)) {
            $orderNr = $this->oxOrder->oxorder__oxordernr->value;
            NetsLog::log($this->_NetsLog, " refupdate NetsOrder, order nr", $this->oxOrder->oxorder__oxordernr->value);
            \oxRegistry::getSession()->setVariable('orderNr', $orderNr);
        }
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $oDb->execute("UPDATE oxnets SET oxordernr = ?,  hash = ?, oxorder_id = ? WHERE transaction_id = ? ", [
            $orderNr,
                    \oxRegistry::getSession()
                    ->getVariable('sess_challenge'),
                    \oxRegistry::getSession()
                    ->getVariable('sess_challenge'),
            $paymentId
        ]);
        $api_return = $this->oCommonHelper->getCurlResponse($this->oCommonHelper->getApiUrl() . $paymentId, "GET");
        $response = json_decode($api_return, true);
        NetsLog::log($this->_NetsLog, " payment api status NetsOrder, response", $response);
        $refUpdate = [
            'reference' => $orderNr,
            'checkoutUrl' => $response['payment']['checkout']['url']
        ];
        //NetsLog::log($this->_NetsLog, " refupdate NetsOrder, order nr", $oOrder->oxorder__oxordernr->value);
        NetsLog::log($this->_NetsLog, " payment api status NetsOrder, response checkout url", $response['payment']['checkout']['url']);
        NetsLog::log($this->_NetsLog, " refupdate NetsOrder, response", $refUpdate);
        $this->oCommonHelper->getCurlResponse($this->oCommonHelper->getUpdateRefUrl($paymentId), 'PUT', json_encode($refUpdate));
        if (\oxRegistry::getConfig()->getConfigParam('nets_autocapture')) {
            $chargeResponse = $this->oCommonHelper->getCurlResponse($this->oCommonHelper->getApiUrl() . $paymentId, 'GET');
            $api_ret = json_decode($chargeResponse, true);
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
        }
        return true;
    }

    /**
     * Function to update order no in oxnets table
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     * @return $oOrderrnr
     */
    public function updateOrdernr($hash)
    {
        $oID = $this->oOrder->getOrderId();
        //$oOrder = \oxNew(\OxidEsales\Eshop\Application\Model\Order::class);
        $this->oxOrder->load($oID);
        $oOrdernr = $this->oxOrder->oxorder__oxordernr->value;
        NetsLog::log($this->_NetsLog, "NetsOrder, updateOrdernr: " . $oOrdernr . " for hash " . $hash);
        if (is_numeric($oOrdernr) && !empty($hash)) {
            $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
            $oDb->execute("UPDATE oxnets SET oxordernr = ? WHERE hash = ?", [
                $oOrdernr,
                $hash
            ]);
            NetsLog::log($this->_NetsLog, "NetsOrder, in if updateOrdernr: " . $oOrdernr . " for hash " . $hash);
        }
        return $oOrdernr;
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
     * Function to get all type of discounts altogether and pass it to nets api
     * @return float
     */
    public function getDiscountSum($basket)
    {
        $discount = 0.0;
        $totalDiscount = $basket->getTotalDiscount();
        if ($totalDiscount) {
            $discount += $totalDiscount->getBruttoPrice();
        }
        // if payment costs are negative, adding them to discount
        if (($costs = $basket->getPaymentCosts()) < 0) {
            $discount += ($costs * - 1);
        }
        // vouchers, coupons
        $vouchers = (array) $basket->getVouchers();
        foreach ($vouchers as $voucher) {
            $discount += round($voucher->dVoucherdiscount, 2);
        }
        // final discount amount
        return round(round($discount, 2) * 100);
    }

    /**
     * Function to check if it embedded checkout
     * @return bool
     */
    public function isEmbedded()
    {
        $mode = \oxRegistry::getConfig()->getConfigParam('nets_checkout_mode');
        $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
        $sSQL_select = "SELECT OXACTIVE FROM oxpayments WHERE oxid = ? LIMIT 1";
        $payMethod = $oDB->getOne($sSQL_select, [
            self::MODULE_NAME
        ]);
        if ($mode == "embedded" && $payMethod == 1) {
            return true;
        }
        return false;
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

}
