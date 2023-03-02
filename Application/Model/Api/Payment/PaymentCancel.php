<?php

namespace Es\NetsEasy\Application\Model\Api\Payment;

use OxidEsales\Eshop\Application\Model\Order;

/**
 * Class PaymentCancel
 *
 * Documentation for the API call: https://developers.nets.eu/nets-easy/en-EU/api/payment-v1/#v1-payments-paymentid-cancels-post
 *
 * @package Es\NetsEasy\Application\Model\Api
 */
class PaymentCancel extends \Es\NetsEasy\Application\Model\Api\OrderItemRequest
{
    /**
     * @var string|null
     */
    protected $sEndpoint = "/v1/payments/{paymentId}/cancels";

    /**
     * @var string|null
     */
    protected $sRequestMethod = self::METHOD_POST;

    /**
     * Sends PaymentCancel request
     *
     * @param  Order $oOrder
     * @return string
     * @throws \Exception
     */
    public function sendRequest($oOrder)
    {
        $aOrderItems = $this->getOrderItems($oOrder, false);
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
