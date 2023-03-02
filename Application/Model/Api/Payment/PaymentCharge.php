<?php

namespace Es\NetsEasy\Application\Model\Api\Payment;

use OxidEsales\Eshop\Application\Model\Order;

/**
 * Class PaymentCharge
 *
 * Documentation for the API call: https://developers.nets.eu/nets-easy/en-EU/api/payment-v1/#v1-payments-paymentid-charges-post
 *
 * @package Es\NetsEasy\Application\Model\Api
 */
class PaymentCharge extends \Es\NetsEasy\Application\Model\Api\OrderItemRequest
{
    /**
     * @var string|null
     */
    protected $sEndpoint = "/v1/payments/{paymentId}/charges";

    /**
     * @var string|null
     */
    protected $sRequestMethod = self::METHOD_POST;

    /**
     * Sends PaymentCharge request
     *
     * @param  Order $oOrder
     * @return string
     * @throws \Exception
     */
    public function sendRequest($oOrder, $sCaptureReference = false, $iQuantity = false)
    {
        $aOrderItems = $this->getOrderItems($oOrder, false);
        if ($sCaptureReference !== false && $iQuantity !== false) {
            $aOrderItems = $this->getPartialOrderItems($aOrderItems, $sCaptureReference, $iQuantity);
        }
        $aParams = [
            'orderItems' => $aOrderItems,
            'amount' => $this->getTotalAmountFromOrderItems($aOrderItems),
        ];
        $this->setUrlParameters([
            '{paymentId}' => $oOrder->oxorder__oxtransid->value,
        ]);
        return $this->sendCurlRequest($aParams);
    }
}
