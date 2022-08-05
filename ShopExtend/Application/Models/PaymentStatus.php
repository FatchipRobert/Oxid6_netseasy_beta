<?php

namespace Es\NetsEasy\ShopExtend\Application\Models;

use Es\NetsEasy\Core\CommonHelper;
use Es\NetsEasy\ShopExtend\Application\Models\OrderItems;

/**
 * Class defines Nets payment status
 */
class PaymentStatus
{

    protected $oCommonHelper;
    protected $paymentStatusObject;
    protected $oOrderItems;

    public function __construct($paymentStatusObject = null, $commonHelper = null)
    {
        $this->oOrderItems = \oxNew(OrderItems::class);

        if (!$paymentStatusObject) {
            $this->paymentStatusObject = $this;
        } else {
            $this->paymentStatusObject = $paymentStatusObject;
        }
        // works only if StaticHelper is not autoloaded yet!
        if (!$commonHelper) {
            $this->oCommonHelper = \oxNew(CommonHelper::class);
        } else {
            $this->oCommonHelper = $commonHelper;
        }
    }

    /**
     * Function to check the nets payment status and display in admin order list backend page
     * @return Payment Status
     */
    public function getEasyStatus($oxoder_id)
    {
        $payment_id = $this->oCommonHelper->getPaymentId($oxoder_id);
        if (empty($payment_id)) {
            $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
            $oDb->execute("UPDATE oxnets SET payment_status = ? WHERE transaction_id = ? ", [
                1,
                $this->oCommonHelper->getPaymentId($oxoder_id)
            ]);
            $oDb->execute("UPDATE oxorder SET oxstorno = ? WHERE oxid = ? ", [
                1,
                $oxoder_id
            ]);
            return array(
                "paymentErr" => "Order is cancelled. Payment not found."
            );
        }
        // Get order db status from oxorder if cancelled
        $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
        $sSQL_select = "SELECT oxstorno FROM oxorder WHERE oxid = ? LIMIT 1";
        $orderCancel = $oDB->getOne($sSQL_select, [
            $oxoder_id
        ]);
        // Get nets payment db status from oxnets if cancelled
        $sSQL_select = "SELECT payment_status FROM oxnets WHERE oxorder_id = ? LIMIT 1";
        $payStatusDb = $oDB->getOne($sSQL_select, [
            $oxoder_id
        ]);
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
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $oDb->execute("UPDATE oxnets SET payment_status = ? WHERE transaction_id = ? ", [
            $allStatus['dbPayStatus'],
            $this->oCommonHelper->getPaymentId($oxoder_id)
        ]);
        return $allStatus;
    }

    /**
     * Function to get payment status
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
                    $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
                    $oDB->Execute("UPDATE oxnets SET partial_amount = '{$partialc}' WHERE oxorder_id = '{$oxoder_id}'");
                    $oDB->Execute("UPDATE oxnets SET charge_id = '{$chargeid}' WHERE oxorder_id = '{$oxoder_id}'");
                    $oDB->Execute("UPDATE oxorder SET oxpaid = '{$chargedate}' WHERE oxid = '{$oxoder_id}'");
                } else if ($pending) {
                    $paymentStatus = "Refund Pending";
                    $langStatus = "refund_pending";
                } else if ($refunded) {
                    if ($reserved != $refunded) {
                        $paymentStatus = "Partial Refunded";
                        $langStatus = "partial_refund";
                        $dbPayStatus = 5; // For payment status as Partial Charged in oxnets db table
                        $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
                        $oDB->Execute("UPDATE oxnets SET partial_amount = '{$partialr}' WHERE oxorder_id = '{$oxoder_id}'");
                        $oDB->Execute("UPDATE oxnets SET charge_id = '{$chargeid}' WHERE oxorder_id = '{$oxoder_id}'");
                        $oDB->Execute("UPDATE oxorder SET oxpaid = '{$chargedate}' WHERE oxid = '{$oxoder_id}'");
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
        return array("payStatus" => $paymentStatus, "langStatus" => $langStatus, "dbPayStatus" => $dbPayStatus);
    }

}
