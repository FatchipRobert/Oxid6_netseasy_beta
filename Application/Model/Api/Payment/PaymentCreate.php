<?php

namespace Es\NetsEasy\Application\Model\Api\Payment;

use OxidEsales\Eshop\Application\Model\Country;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\OrderArticle;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Price;
use Es\NetsEasy\Application\Helper\Api;

/**
 * Class PaymentCreate
 *
 * Documentation for the API call: https://developers.nets.eu/nets-easy/en-EU/api/payment-v1/#v1-payments-post
 *
 * @package Es\NetsEasy\Application\Model\Api
 */
class PaymentCreate extends \Es\NetsEasy\Application\Model\Api\OrderItemRequest
{
    /**
     * @var string|null
     */
    protected $sEndpoint = "/v1/payments";

    /**
     * Returns the countries iso3 code by given country id
     *
     * @param string $sCountryId
     * @return string
     */
    protected function getCountryCodeById($sCountryId)
    {
        $oCountry = oxNew(Country::class);
        $oCountry->load($sCountryId);
        return $oCountry->oxcountry__oxisoalpha3->value;
    }

    /**
     * Returns consumer data array for api request
     *
     * @param  Order $oOrder
     * @return array
     */
    protected function getConsumerData($oOrder)
    {
        $aConsumer = [
            'email' => $oOrder->oxorder__oxbillemail->value,
            'shippingAddress' => [
                'addressLine1' => !empty($oOrder->oxorder__oxdelcountryid->value) ? $oOrder->oxorder__oxdelstreet->value." ".$oOrder->oxorder__oxdelstreetnr->value : $oOrder->oxorder__oxbillstreet->value." ".$oOrder->oxorder__oxbillstreetnr->value,
                'postalCode'   => !empty($oOrder->oxorder__oxdelcountryid->value) ? $oOrder->oxorder__oxdelzip->value : $oOrder->oxorder__oxbillzip->value,
                'city'         => !empty($oOrder->oxorder__oxdelcountryid->value) ? $oOrder->oxorder__oxdelcity->value : $oOrder->oxorder__oxbillcity->value,
                'country'      => $this->getCountryCodeById(!empty($oOrder->oxorder__oxdelcountryid->value) ? $oOrder->oxorder__oxdelcountryid->value : $oOrder->oxorder__oxbillcountryid->value),
            ],
        ];
        if (!empty($oOrder->oxorder__oxdelcountryid->value) && !empty($oOrder->oxorder__oxdelcompany->value)) {
            $aConsumer['company'] = [
                'name' => $oOrder->oxorder__oxdelcompany->value,
                'contact' => [
                    'firstName' => $oOrder->oxorder__oxdelfname->value,
                    'lastName' => $oOrder->oxorder__oxdellname->value
                ]
            ];
        } elseif(!empty($oOrder->oxorder__oxbillcompany->value)) {
            $aConsumer['company'] = [
                'name' => $oOrder->oxorder__oxbillcompany->value,
                'contact' => [
                    'firstName' => $oOrder->oxorder__oxbillfname->value,
                    'lastName' => $oOrder->oxorder__oxbilllname->value
                ]
            ];
        } else {
            $aConsumer['privatePerson'] = [
                'firstName' => !empty($oOrder->oxorder__oxdelcountryid->value) ? $oOrder->oxorder__oxdelfname->value : $oOrder->oxorder__oxbillfname->value,
                'lastName'  => !empty($oOrder->oxorder__oxdelcountryid->value) ? $oOrder->oxorder__oxdellname->value : $oOrder->oxorder__oxbilllname->value,
            ];
        }
        return $aConsumer;
    }

    /**
     * Collect all safety parameters that are needed for jumping right back in the same checkout session
     *
     * @return string
     */
    protected function getReturnParameters()
    {
        $sReturnParams = '';

        $aUrlCopyParameters = ['stoken', 'sDeliveryAddressMD5', 'oxdownloadableproductsagreement', 'oxserviceproductsagreement'];
        foreach ($aUrlCopyParameters as $sParamName) {
            $sValue = Registry::getRequest()->getRequestEscapedParameter($sParamName);
            if (!empty($sValue)) {
                $sReturnParams .= '&'.$sParamName.'='.$sValue;
            }
        }

        if (!Registry::getRequest()->getRequestEscapedParameter('stoken')) {
            $sReturnParams .= '&stoken='.Registry::getSession()->getSessionChallengeToken();
        }

        $sSid = Registry::getSession()->sid(true);
        if ($sSid != '') {
            $sReturnParams .= '&'.$sSid;
        }

        $sReturnParams .= '&ord_agb=1';
        $sReturnParams .= '&rtoken='.Registry::getSession()->getRemoteAccessToken();

        return $sReturnParams;
    }

    /**
     * Returns checkout data for api request
     *
     * @param  Order $oOrder
     * @return array
     */
    protected function getCheckoutData($oOrder)
    {
        $aCheckout = [
            'integrationType' => Api::getInstance()->getIntegrationType(),
            'termsUrl' => Registry::getConfig()->getConfigParam('nets_terms_url'),
            'merchantTermsUrl' => Registry::getConfig()->getConfigParam('nets_merchant_terms_url'),
            'merchantHandlesConsumerData' => true,
            'consumer' => $this->getConsumerData($oOrder),
        ];

        if (Api::getInstance()->isEmbeddedMode()) {
            $aCheckout['url'] = urldecode(Registry::getConfig()->getCurrentShopUrl().'index.php?cl=order&fnc=execute');
        } else {
            $aCheckout['returnUrl'] = urldecode(Registry::getConfig()->getCurrentShopUrl().'index.php?cl=order&fnc=netsReturnHosted'.$this->getReturnParameters());
            $aCheckout['cancelUrl'] = urldecode(Registry::getConfig()->getCurrentShopUrl().'index.php?cl=payment');
        }

        if (Registry::getConfig()->getConfigParam('nets_autocapture')) {
            $aCheckout['charge'] = true;
        }
        return $aCheckout;
    }

    /**
     * @param  Order $oOrder
     * @return array
     */
    protected function getParamsFromOrder($oOrder)
    {
        $aParams = [
            'order' => [
                'items' => $this->getOrderItems($oOrder),
                'amount' => Api::getInstance()->formatPrice($oOrder->oxorder__oxtotalordersum->value),
                'currency' => $oOrder->oxorder__oxcurrency->value,
                'reference' => $oOrder->getId()
            ],
            'checkout' => $this->getCheckoutData($oOrder),
        ];
        return $aParams;
    }

    /**
     * Sends CreatePayment request
     *
     * @param  Order $oOrder
     * @return string
     * @throws \Exception
     */
    public function sendRequest($oOrder)
    {
        $aParams = $this->getParamsFromOrder($oOrder);
        return $this->sendCurlRequest($aParams);
    }
}
