<?php

namespace Es\NetsEasy\ShopExtend\Application\Models;

use Es\NetsEasy\Api\NetsLog;
use Es\NetsEasy\Api\NetsPaymentTypes;
use Es\NetsEasy\Core\CommonHelper;
use Es\NetsEasy\ShopExtend\Application\Models\OrderItems;

/**
 * Class defines Nets payment operations in order view
 */
class PaymentOperations
{

    protected $oCommonHelper;
    protected $paymentOPObject;
    protected $_NetsLog;
    protected $netsLog;
    protected $oOrderItems;

    public function __construct($paymentOPObject = null, $commonHelper = null, $oOrderItems = null)
    {
        $this->_NetsLog = true;
        $this->netsLog = \oxNew(NetsLog::class);

        if (!$paymentOPObject) {
            $this->paymentOPObject = $this;
        } else {
            $this->paymentOPObject = $paymentOPObject;
        }
        // works only if StaticHelper is not autoloaded yet!
        if (!$commonHelper) {
            $this->oCommonHelper = \oxNew(CommonHelper::class);
        } else {
            $this->oCommonHelper = $commonHelper;
        }
        if (!$oOrderItems) {
            $this->oOrderItems = \oxNew(OrderItems::class);
        } else {
            $this->oOrderItems = $oOrderItems;
        }
    }

    /**
     * Function to capture nets transaction - calls Charge API
     * @return array
     */
    public function getOrderCharged()
    {
        $oxorder = \oxRegistry::getConfig()->getRequestParameter('oxorderid');
        $orderno = \oxRegistry::getConfig()->getRequestParameter('orderno');
        $data = $this->oOrderItems->getOrderItems($oxorder);
        $payment_id = $this->oCommonHelper->getPaymentId($oxorder);
        // call charge api here
        $chargeUrl = $this->oCommonHelper->getChargePaymentUrl($payment_id);
        $ref = \oxRegistry::getConfig()->getRequestParameter('reference');
        $chargeQty = \oxRegistry::getConfig()->getRequestParameter('charge');
        if (isset($ref) && isset($chargeQty)) {
            $totalAmount = 0;
            foreach ($data['items'] as $key => $value) {
                if (in_array($ref, $value) && $ref === $value['reference']) {
                    $value = $this->paymentOPObject->getValueItem($value, $chargeQty);
                    $itemList[] = $value;
                    $totalAmount += $value['grossTotalAmount'];
                }
            }
            $body = [
                'amount' => $totalAmount,
                'orderItems' => $itemList
            ];
        } else {
            $body = [
                'amount' => $data['totalAmt'],
                'orderItems' => $data['items']
            ];
        }
        $this->netsLog->log($this->_NetsLog, "Nets_Order_Overview" . json_encode($body));
        $api_return = $this->oCommonHelper->getCurlResponse($chargeUrl, 'POST', json_encode($body));
        $response = json_decode($api_return, true);

        $this->netsLog->log($this->_NetsLog, "Nets_Order_Overview" . $response);
        $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
        $dt = date("Y-m-d H:i:s");
        $oDB->Execute("UPDATE oxorder SET oxpaid = '{$dt}'
		WHERE oxid = '{$oxorder}'");
        // save charge details in db for partial refund
        if (isset($ref) && isset($response['chargeId'])) {
            $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
            $charge_query = "INSERT INTO `oxnets` (`transaction_id`, `charge_id`,  `product_ref`, `charge_qty`, `charge_left_qty`) " . "values ('" . $payment_id . "', '" . $response['chargeId'] . "', '" . $ref . "', '" . $chargeQty . "', '" . $chargeQty . "')";
            $oDB->Execute($charge_query);
        } else {
            $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
            if (isset($response['chargeId'])) {
                foreach ($data['items'] as $key => $value) {
                    $charge_query = "INSERT INTO `oxnets` (`transaction_id`,`charge_id`,  `product_ref`, `charge_qty`, `charge_left_qty`) " . "values ('" . $payment_id . "', '" . $response['chargeId'] . "', '" . $value['reference'] . "', '" . $value['quantity'] . "', '" . $value['quantity'] . "')";
                    $oDB->Execute($charge_query);
                }
            }
        }
        return true;
    }

    /*
     * Function to get value item list for charge
     * return int
     */

    public function getValueItem($value, $chargeQty)
    {
        $value['quantity'] = $chargeQty;
        $prodPrice = $value['oxbprice']; // product price incl. VAT in DB format
        $tax = (int) $value['taxRate'] / 100; // Tax rate in DB format
        $taxFormat = '1' . str_pad(number_format((float) $tax, 2, '.', ''), 5, '0', STR_PAD_LEFT);
        $unitPrice = round(round(($prodPrice * 100) / $taxFormat, 2) * 100);
        $netAmount = round($chargeQty * $unitPrice);
        $grossAmount = round($chargeQty * ($prodPrice * 100));
        $value['netTotalAmount'] = $netAmount;
        $value['grossTotalAmount'] = $grossAmount;
        $value['taxAmount'] = $grossAmount - $netAmount;
        unset($value['oxbprice']);
        return $value;
    }

    /*
     * Function to capture nets transaction - calls Refund API
     * redirects to admin overview listing page
     */

    public function getOrderRefund()
    {
        $oxorder = \oxRegistry::getConfig()->getRequestParameter('oxorderid');
        $orderno = \oxRegistry::getConfig()->getRequestParameter('orderno');
        $data = $this->oOrderItems->getOrderItems($oxorder);

        $oCommonHelper = new CommonHelper();
        $api_return = $oCommonHelper->getCurlResponse($this->oCommonHelper->getApiUrl() . $this->oCommonHelper->getPaymentId($oxorder), 'GET');
        $response = json_decode($api_return, true);

        $chargeResponse = $this->paymentOPObject->getChargeId($oxorder);
        $ref = \oxRegistry::getConfig()->getRequestParameter('reference');
        $refundQty = \oxRegistry::getConfig()->getRequestParameter('refund');
        $payment_id = $this->oCommonHelper->getPaymentId($oxorder);
        $refundEachQtyArr = array();
        $breakloop = false;
        $cnt = 1;

        foreach ($chargeResponse['response']['payment']['charges'] as $ky => $val) {
            if (empty($ref)) {
                $body = [
                    'amount' => $val['amount'],
                    'orderItems' => $val['orderItems']
                ];
                $refundUrl = $this->oCommonHelper->getRefundPaymentUrl($val['chargeId']);
                $this->oCommonHelper->getCurlResponse($refundUrl, 'POST', json_encode($body));
                // table update forcharge refund quantity
                $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
                $oDb->execute("UPDATE oxnets SET charge_left_qty = 0 WHERE transaction_id = '" . $payment_id . "' AND charge_id = '" . $val['chargeId'] . "'");

                $this->netsLog->log($this->_NetsLog, "Nets_Order_Overview getorder refund" . json_encode($body));
            } else if (in_array($ref, array_column($val['orderItems'], 'reference'))) {
                $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(\OxidEsales\Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC);
                $charge_query = $oDb->getAll("SELECT `transaction_id`, `charge_id`,  `product_ref`, `charge_qty`, `charge_left_qty` FROM oxnets WHERE transaction_id = ? AND charge_id = ? AND product_ref = ? AND charge_left_qty !=0", [
                    $payment_id,
                    $val['chargeId'],
                    $ref
                ]);
                if (count($charge_query) > 0) {
                    $table_charge_left_qty = $refundEachQtyArr[$val['chargeId']] = $charge_query[0]['charge_left_qty'];
                }
                if ($refundQty <= array_sum($refundEachQtyArr)) {
                    $leftqtyFromArr = array_sum($refundEachQtyArr) - $refundQty;
                    $leftqty = $table_charge_left_qty - $leftqtyFromArr;
                    $refundEachQtyArr[$val['chargeId']] = $leftqty;
                    $breakloop = true;
                }
                if ($breakloop) {
                    foreach ($refundEachQtyArr as $key => $value) {
                        $body = $this->paymentOPObject->getItemForRefund($ref, $value, $data);

                        $refundUrl = $this->oCommonHelper->getRefundPaymentUrl($key);
                        $this->oCommonHelper->getCurlResponse($refundUrl, 'POST', json_encode($body));
                        $this->netsLog->log($this->_NetsLog, "Nets_Order_Overview getorder refund" . json_encode($body));

                        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(\OxidEsales\Eshop\Core\DatabaseProvider::FETCH_MODE_ASSOC);
                        $singlecharge_query = $oDb->getAll("SELECT  `charge_left_qty` FROM oxnets WHERE transaction_id = ? AND charge_id = ? AND product_ref = ? AND charge_left_qty !=0", [
                            $payment_id,
                            $val['chargeId'],
                            $ref
                        ]);
                        if (count($singlecharge_query) > 0) {
                            $charge_left_qty = $singlecharge_query[0]['charge_left_qty'];
                        }
                        $charge_left_qty = $value - $charge_left_qty;
                        if ($charge_left_qty < 0) {
                            $charge_left_qty = - $charge_left_qty;
                        }
                        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
                        $oDb->execute("UPDATE oxnets SET charge_left_qty = $charge_left_qty WHERE transaction_id = '" . $payment_id . "' AND charge_id = '" . $key . "' AND product_ref = '" . $ref . "'");
                    }
                    break;
                }
            }
        }
    }

    /*
     * Function to fetch charge id from databse table oxnets
     * @param $oxorder_id
     * @return nets charge id
     */

    public function getChargeId($oxoder_id)
    {
        // Get charge id from nets payments api
        $api_return = $this->oCommonHelper->getCurlResponse($this->oCommonHelper->getApiUrl() . $this->oCommonHelper->getPaymentId($oxoder_id), 'GET');
        $response = json_decode($api_return, true);

        $chargesMap = array_map(function ($element) {
            return $element['chargeId'];
        }, $response['payment']['charges']);

        if (count($chargesMap) == 1) {
            $result = array(
                "chargeId" => $response['payment']['charges'][0]['chargeId']
            );
        } else {
            $result = array(
                "chargeId" => $chargesMap
            );
        }
        $result["response"] = $response;
        return $result;
    }

    /*
     * Function to Get order Items to refund and pass them to refund api
     * @return array
     */

    public function getItemForRefund($ref, $refundQty, $data)
    {
        $totalAmount = 0;
        foreach ($data['items'] as $key => $value) {
            if ($ref === $value['reference']) {
                $value['quantity'] = $refundQty;
                $prodPrice = $value['oxbprice']; // product price incl. VAT in DB format
                $tax = (int) $value['taxRate'] / 100; // Tax rate in DB format
                $taxFormat = '1' . str_pad(number_format((float) $tax, 2, '.', ''), 5, '0', STR_PAD_LEFT);
                $unitPrice = round(round(($prodPrice * 100) / $taxFormat, 2) * 100);
                $netAmount = round($refundQty * $unitPrice);
                $grossAmount = round($refundQty * ($prodPrice * 100));
                $value['netTotalAmount'] = $netAmount;
                $value['grossTotalAmount'] = $grossAmount;
                $value['taxAmount'] = $grossAmount - $netAmount;
                unset($value['oxbprice']);
                $itemList[] = $value;
                $totalAmount += $grossAmount;
            }
        }
        $body = [
            'amount' => $totalAmount,
            'orderItems' => $itemList
        ];
        return $body;
    }

}
