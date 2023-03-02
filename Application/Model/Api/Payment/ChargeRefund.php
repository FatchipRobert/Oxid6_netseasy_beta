<?php

namespace Es\NetsEasy\Application\Model\Api\Payment;

use OxidEsales\Eshop\Application\Model\Order;

/**
 * Class ChargeRefund
 *
 * Documentation for the API call: https://developers.nets.eu/nets-easy/en-EU/api/payment-v1/#v1-charges-chargeid-refunds-post
 *
 * @package Es\NetsEasy\Application\Model\Api
 */
class ChargeRefund extends \Es\NetsEasy\Application\Model\Api\OrderItemRequest
{
    /**
     * @var string|null
     */
    protected $sEndpoint = "/v1/charges/{chargeId}/refunds";

    /**
     * @var string|null
     */
    protected $sRequestMethod = self::METHOD_POST;

    /**
     * Sends ChargeRefund request
     *
     * @param  array $aCharge
     * @return string
     * @throws \Exception
     */
    protected function sendRequest($sChargeId, $aParams)
    {
        $this->setUrlParameters([
            '{chargeId}' => $sChargeId,
        ]);
        return $this->sendCurlRequest($aParams);
    }

    /**
     * Sends ChargeRefund request by given charge array
     *
     * @param  array $aCharge
     * @return string
     * @throws \Exception
     */
    public function sendRequestByCharge($aCharge)
    {
        $aParams = [
            'amount' => $aCharge['amount'],
            'orderItems' => $aCharge['orderItems']
        ];
        return $this->sendRequest($aCharge['chargeId'], $aParams);
    }

    /**
     * Sends partial ChargeRefund request by given reference and quantity
     *
     * @param Order $oOrder
     * @param string $sChargeId
     * @param string $sRefundReference
     * @param int $iQuantity
     * @return string
     */
    public function sendRequestByReference($oOrder, $sChargeId, $sRefundReference, $iQuantity)
    {
        $aOrderItems = $this->getOrderItems($oOrder, false);
        $aOrderItems = $this->getPartialOrderItems($aOrderItems, $sRefundReference, $iQuantity);
        $aParams = [
            'orderItems' => $aOrderItems,
            'amount' => $this->getTotalAmountFromOrderItems($aOrderItems),
        ];
        return $this->sendRequest($sChargeId, $aParams);
    }
}
