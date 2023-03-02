<?php

namespace Es\NetsEasy\Application\Model\Api\Payment;

/**
 * Class ReferenceInformationUpdate
 *
 * Documentation for the API call: https://developers.nets.eu/nets-easy/en-EU/api/payment-v1/#v1-payments-paymentid-referenceinformation-put
 *
 * @package Es\NetsEasy\Application\Model\Api
 */
class ReferenceInformationUpdate extends \Es\NetsEasy\Application\Model\Api\BaseRequest
{
    /**
     * @var string|null
     */
    protected $sEndpoint = "/v1/payments/{paymentId}/referenceinformation";

    /**
     * @var string|null
     */
    protected $sRequestMethod = self::METHOD_PUT;

    /**
     * Sends ReferenceInformationUpdate request
     *
     * @param  string $sPaymentId
     * @param  array $aParams
     * @return string
     * @throws \Exception
     */
    public function sendRequest($sPaymentId, $aParams)
    {
        $this->setUrlParameters([
            '{paymentId}' => $sPaymentId,
        ]);
        return $this->sendCurlRequest($aParams);
    }
}
