<?php

namespace Es\NetsEasy\Application\Model;

use Es\NetsEasy\Application\Model\Api\Payment\PaymentRetrieve;
use Es\NetsEasy\Application\Model\ResourceModel\NetsTransactions;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

/**
 * Class defines Nets payment status
 */
class PaymentStatus
{
    /**
     * Returns new query builder object
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function getQueryBuilder()
    {
        return ContainerFactory::getInstance()
            ->getContainer()
            ->get(QueryBuilderFactoryInterface::class)
            ->create();
    }

    /**
     * Function to check the nets payment status and display in admin order list backend page
     *
     * @param  Order $oOrder
     * @return array
     */
    public function checkEasyStatus($oOrder)
    {
        if (empty($oOrder->oxorder__oxtransid->value)) {
            $oNetsTransaction = oxNew(NetsTransactions::class);
            $oNetsTransaction->updatePaymentStatus(NetsTransactions::STATUS_CANCELLED, $oOrder->getId());

            $oOrder->cancelOrder();
            return ["paymentErr" => "Order is cancelled. Payment not found."];
        }

        $oNetsTransaction = oxNew(NetsTransactions::class);
        $iPaymentStatus = $oNetsTransaction->getPaymentStatusByOrderId($oOrder->getId());
        // if order is cancelled and payment is not updated as cancelled, call nets cancel payment api
        if ($oOrder->oxorder__oxstorno->value == 1 && $iPaymentStatus != NetsTransactions::STATUS_CANCELLED) {
            try {
                $oOrder->netsCancelOrder();
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }
        try {
            // Get payment status from nets payments api
            $oPaymentInfo = oxNew(PaymentRetrieve::class);
            $aResponse = $oPaymentInfo->sendRequest($oOrder->oxorder__oxtransid->value);
        } catch (Exception $e) {
            return $e->getMessage();
        }
        $allStatus = $this->getPaymentStatus($aResponse, $oOrder);

        $oNetsTransaction = oxNew(NetsTransactions::class);
        $oNetsTransaction->updatePaymentStatus($allStatus['dbPayStatus'], $oOrder->getId());

        return $allStatus;
    }

    /**
     * Function to get payment status
     *
     * @param  array $response
     * @param  Order $oOrder
     * @return array
     */
    public function getPaymentStatus($response, $oOrder)
    {
        $oNetsTransaction = oxNew(NetsTransactions::class);

        $dbPayStatus = '';
        $pending = '';
        $cancelledAmount = isset($response['payment']['summary']['cancelledAmount']) ? $response['payment']['summary']['cancelledAmount'] : '0';
        $reservedAmount = isset($response['payment']['summary']['reservedAmount']) ? $response['payment']['summary']['reservedAmount'] : '0';
        $chargedAmount = isset($response['payment']['summary']['chargedAmount']) ? $response['payment']['summary']['chargedAmount'] : '0';
        $refundedAmount = isset($response['payment']['summary']['refundedAmount']) ? $response['payment']['summary']['refundedAmount'] : '0';
        if (isset($response['payment']['refunds'])) {
            if (in_array("Pending", array_column($response['payment']['refunds'], 'state'))) {
                $pending = "Pending";
            }
        }
        $partialChargedAmount = $reservedAmount - $chargedAmount;
        $partialRefundedAmount = $reservedAmount - $refundedAmount;
        $chargeid = isset($response['payment']['charges'][0]['chargeId']) ? $response['payment']['charges'][0]['chargeId'] : '';
        $chargedate = isset($response['payment']['charges'][0]['created']) ? $response['payment']['charges'][0]['created'] : date('Y-m-d');
        $A2A = $response['payment']['paymentDetails']['paymentType'] == 'A2A' ? TRUE : FALSE;
        if ($A2A) {
            $reservedAmount = isset($response['payment']['summary']['chargedAmount']) ? $response['payment']['summary']['chargedAmount'] : '0';
        }
        if ($reservedAmount) {
            if ($cancelledAmount) {
                $langStatus = "cancel";
                $paymentStatus = "Cancelled";
                $dbPayStatus = NetsTransactions::STATUS_CANCELLED; // For payment status as cancelled in nets_transactions db table
            } elseif ($chargedAmount) {
                if ($reservedAmount != $chargedAmount) {
                    $paymentStatus = "Partial Charged";
                    $langStatus = "partial_charge";
                    $dbPayStatus = NetsTransactions::STATUS_PARTIALLY_CHARGED; // For payment status as Partial Charged in nets_transactions db table

                    $oNetsTransaction->updatePartialAmountAndChargeId($partialChargedAmount, $chargeid, $oOrder->getId());

                    $oOrder->netsMarkAsPaid($chargedate);
                } elseif ($pending) {
                    $paymentStatus = "Refund Pending";
                    $langStatus = "refund_pending";
                } elseif ($refundedAmount) {
                    if ($reservedAmount != $refundedAmount) {
                        $paymentStatus = "Partial Refunded";
                        $langStatus = "partial_refund";
                        $dbPayStatus = NetsTransactions::STATUS_PARTIALLY_REFUNDED; // For payment status as Partial Charged in nets_transactions db table

                        $oNetsTransaction->updatePartialAmountAndChargeId($partialChargedAmount, $chargeid, $oOrder->getId());

                        $oOrder->netsMarkAsPaid($chargedate);
                    } else {
                        $paymentStatus = "Refunded";
                        $langStatus = "refunded";
                        $dbPayStatus = NetsTransactions::STATUS_REFUNDED; // For payment status as Refunded in nets_transactions db table
                    }
                } else {
                    $paymentStatus = "Charged";
                    $langStatus = "charged";
                    $dbPayStatus = NetsTransactions::STATUS_CHARGED; // For payment status as Charged in nets_transactions db table
                }
            } else {
                $paymentStatus = 'Reserved';
                $langStatus = "reserved";
                $dbPayStatus = NetsTransactions::STATUS_AUTHORIZED; // For payment status as Authorized in nets_transactions db table
            }
        } else {
            $paymentStatus = "Failed";
            $langStatus = "failed";
            $dbPayStatus = NetsTransactions::STATUS_FAILED; // For payment status as Failed in nets_transactions db table
        }
        return ["payStatus" => $paymentStatus, "langStatus" => $langStatus, "dbPayStatus" => $dbPayStatus];
    }
}
