<?php

namespace Es\NetsEasy\Application\Model\Api\Payment;

/**
 * Class PaymentRetrieve
 *
 * Documentation for the API call: https://developers.nets.eu/nets-easy/en-EU/api/payment-v1/#v1-payments-paymentid-get
 *
 * @package Es\NetsEasy\Application\Model\Api
 */
class PaymentRetrieve extends \Es\NetsEasy\Application\Model\Api\BaseRequest
{
    /**
     * @var string|null
     */
    protected $sEndpoint = "/v1/payments/{paymentId}";

    /**
     * @var string|null
     */
    protected $sRequestMethod = self::METHOD_GET;

    /**
     * Sends PaymentRetrieve request
     *
     * @param  string $sPaymentId
     * @return string
     * @throws \Exception
     */
    public function sendRequest($sPaymentId)
    {
        $this->setUrlParameters([
            '{paymentId}' => $sPaymentId,
        ]);
        return $this->sendCurlRequest();
    }
}
