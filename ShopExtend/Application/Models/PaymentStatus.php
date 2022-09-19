<?php

namespace Es\NetsEasy\ShopExtend\Application\Models;

use Es\NetsEasy\Core\CommonHelper;
use Es\NetsEasy\ShopExtend\Application\Models\OrderItems;
use \OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use \OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

/**
 * Class defines Nets payment status
 */
class PaymentStatus
{

    protected $oCommonHelper;
    protected $paymentStatusObject;
    protected $oOrderItems;
    protected $queryBuilder;

    /**
     * Constructor
     * @param object $paymentStatusObject The OXID PaymentStatus model
     * @param object $commonHelper The service file injected as object
     * @return Null
     */
    public function __construct($paymentStatusObject = null, CommonHelper $commonHelper)
    {
        $this->oOrderItems = \oxNew(OrderItems::class, null, \oxNew(\Es\NetsEasy\Core\CommonHelper::class));
        $this->queryBuilder = ContainerFactory::getInstance()
                ->getContainer()
                ->get(QueryBuilderFactoryInterface::class);

        if (!$paymentStatusObject) {
            $this->paymentStatusObject = $this;
        } else {
            $this->paymentStatusObject = $paymentStatusObject;
        }
        // works only if StaticHelper is not autoloaded yet!
        $this->oCommonHelper = $commonHelper;
    }

    /**
     * Function to check the nets payment status and display in admin order list backend page
     * @param string $oxoder_id The OXID Order ID
     * @return string Payment Status
     */
    public function getEasyStatus($oxoder_id)
    {
        $payment_id = $this->oCommonHelper->getPaymentId($oxoder_id);
        if (empty($payment_id)) {
            $queryBuilder = $this->queryBuilder->create();
            $queryBuilder
                    ->update('oxnets', 'o')
                    ->set('o.payment_status', '?')
                    ->where('o.transaction_id = ?')
                    ->setParameter(0, 1)
                    ->setParameter(1, $this->oCommonHelper->getPaymentId($oxoder_id))
            ;
            $queryData = $queryBuilder->execute();
            $queryBuilder = $this->queryBuilder->create();
            $queryBuilder
                    ->update('oxorder', 'o')
                    ->set('o.oxstorno', '?')
                    ->where('o.oxid = ?')
                    ->setParameter(0, 1)
                    ->setParameter(1, $oxoder_id)
            ;
            $queryBuilder->execute();
            return [
                "paymentErr" => "Order is cancelled. Payment not found."
            ];
        }
        // Get order db status from oxorder if cancelled

        $queryBuilder = $this->queryBuilder->create();
        $queryBuilder
                ->select('oxstorno')
                ->from('oxorder')
                ->where('oxid = :oxorder_id')
                ->setParameters([
                    'oxorder_id' => $oxoder_id,
        ]);
        $orderCancel = $queryBuilder->execute()->fetchOne();
        // Get nets payment db status from oxnets if cancelled

        $queryBuilder = $this->queryBuilder->create();
        $queryBuilder
                ->select('payment_status')
                ->from('oxnets')
                ->where('oxorder_id = :oxorder_id')
                ->setParameters([
                    'oxorder_id' => $oxoder_id,
        ]);
        $payStatusDb = $queryBuilder->execute()->fetchOne();
        // if order is cancelled and payment is not updated as cancelled, call nets cancel payment api
        if ($orderCancel && $payStatusDb != 1) {
            $data = $this->oOrderItems->getOrderItems($oxoder_id, false);
            // call cancel api here
            $cancelUrl = $this->oCommonHelper->getVoidPaymentUrl($payment_id);
            $cancelBody = [
                'amount' => $data['totalAmt'],
                'orderItems' => $data['items']
            ];
            try {
                $this->oCommonHelper->getCurlResponse($cancelUrl, 'POST', json_encode($cancelBody));
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }
        try {
            // Get payment status from nets payments api
            $api_return = $this->oCommonHelper->getCurlResponse($this->oCommonHelper->getApiUrl() . $this->oCommonHelper->getPaymentId($oxoder_id), 'GET');
            $response = json_decode($api_return, true);
        } catch (Exception $e) {
            return $e->getMessage();
        }
        $allStatus = $this->paymentStatusObject->getPaymentStatus($response, $oxoder_id);

        $queryBuilder = $this->queryBuilder->create();
        $queryBuilder
                ->update('oxnets', 'o')
                ->set('o.payment_status', '?')
                ->where('o.transaction_id = ?')
                ->setParameter(0, $allStatus['dbPayStatus'])
                ->setParameter(1, $this->oCommonHelper->getPaymentId($oxoder_id))
        ;
        $queryData = $queryBuilder->execute();
        return $allStatus;
    }

    /**
     * Function to get payment status
     * @param array $response The NETS API response
     * @param string $oxoder_id The OXID Order ID
     * @return array
     */
    public function getPaymentStatus($response, $oxoder_id)
    {
        $dbPayStatus = '';
        $paymentStatus = '';
        $pending = '';
        $cancelled = isset($response['payment']['summary']['cancelledAmount']) ? $response['payment']['summary']['cancelledAmount'] : '0';
        $reserved = isset($response['payment']['summary']['reservedAmount']) ? $response['payment']['summary']['reservedAmount'] : '0';
        $charged = isset($response['payment']['summary']['chargedAmount']) ? $response['payment']['summary']['chargedAmount'] : '0';
        $refunded = isset($response['payment']['summary']['refundedAmount']) ? $response['payment']['summary']['refundedAmount'] : '0';
        if (isset($response['payment']['refunds'])) {
            if (in_array("Pending", array_column($response['payment']['refunds'], 'state'))) {
                $pending = "Pending";
            }
        }
        $partialc = $reserved - $charged;
        $partialr = $reserved - $refunded;
        $chargeid = isset($response['payment']['charges'][0]['chargeId']) ? $response['payment']['charges'][0]['chargeId'] : '';
        $chargedate = isset($response['payment']['charges'][0]['created']) ? $response['payment']['charges'][0]['created'] : date('Y-m-d');
        $A2A = $response['payment']['paymentDetails']['paymentType'] == 'A2A' ? TRUE : FALSE;
        if ($A2A) {
            $reserved = isset($response['payment']['summary']['chargedAmount']) ? $response['payment']['summary']['chargedAmount'] : '0';
        }
        if ($reserved) {
            if ($cancelled) {
                $langStatus = "cancel";
                $paymentStatus = "Cancelled";
                $dbPayStatus = 1; // For payment status as cancelled in oxnets db table
            } else if ($charged) {
                if ($reserved != $charged) {
                    $paymentStatus = "Partial Charged";
                    $langStatus = "partial_charge";
                    $dbPayStatus = 3; // For payment status as Partial Charged in oxnets db table

                    $queryBuilder = $this->queryBuilder->create();
                    $queryBuilder->update('oxnets', 'o')->set('o.partial_amount', '?')->where('o.oxorder_id = ?')->setParameter(0, "'{$partialc}'")->setParameter(1, "'{$oxoder_id}'")->execute();
                    $queryBuilder = $this->queryBuilder->create();
                    $queryBuilder->update('oxnets', 'o')->set('o.charge_id', '?')->where('o.oxorder_id = ?')->setParameter(0, "'{$chargeid}'")->setParameter(1, "'{$oxoder_id}'")->execute();
                    $queryBuilder = $this->queryBuilder->create();
                    $queryBuilder->update('oxorder', 'o')->set('o.oxpaid', '?')->where('o.oxid = ?')->setParameter(0, "'{$chargedate}'")->setParameter(1, "'{$oxoder_id}'")->execute();
                } else if ($pending) {
                    $paymentStatus = "Refund Pending";
                    $langStatus = "refund_pending";
                } else if ($refunded) {
                    if ($reserved != $refunded) {
                        $paymentStatus = "Partial Refunded";
                        $langStatus = "partial_refund";
                        $dbPayStatus = 5; // For payment status as Partial Charged in oxnets db table

                        $queryBuilder = $this->queryBuilder->create();
                        $queryBuilder->update('oxnets', 'o')->set('o.partial_amount', '?')->where('o.oxorder_id = ?')->setParameter(0, "'{$partialc}'")->setParameter(1, "'{$oxoder_id}'")->execute();
                        $queryBuilder = $this->queryBuilder->create();
                        $queryBuilder->update('oxnets', 'o')->set('o.charge_id', '?')->where('o.oxorder_id = ?')->setParameter(0, "'{$chargeid}'")->setParameter(1, "'{$oxoder_id}'")->execute();
                        $queryBuilder = $this->queryBuilder->create();
                        $queryBuilder->update('oxorder', 'o')->set('o.oxpaid', '?')->where('o.oxid = ?')->setParameter(0, "'{$chargedate}'")->setParameter(1, "'{$oxoder_id}'")->execute();
                    } else {
                        $paymentStatus = "Refunded";
                        $langStatus = "refunded";
                        $dbPayStatus = 6; // For payment status as Refunded in oxnets db table
                    }
                } else {
                    $paymentStatus = "Charged";
                    $langStatus = "charged";
                    $dbPayStatus = 4; // For payment status as Charged in oxnets db table
                }
            } else {
                $paymentStatus = 'Reserved';
                $langStatus = "reserved";
                $dbPayStatus = 2; // For payment status as Authorized in oxnets db table
            }
        } else {
            $paymentStatus = "Failed";
            $langStatus = "failed";
            $dbPayStatus = 0; // For payment status as Failed in oxnets db table
        }
        return ["payStatus" => $paymentStatus, "langStatus" => $langStatus, "dbPayStatus" => $dbPayStatus];
    }

}
