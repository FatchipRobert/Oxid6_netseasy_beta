<?php

namespace Es\NetsEasy\extend\Application\Models;

use Es\NetsEasy\Api\NetsLog;
use Es\NetsEasy\Api\NetsPaymentTypes;
use Es\NetsEasy\Core\CommonHelper;

/**
 * Class defines Nets payment operations in order view
 */
class OrderOverview
{

    protected $oCommonHelper;
    protected $oOrderOverview;
    protected $_NetsLog;

    public function __construct($oOrderOverview = null, $commonHelper = null)
    {
        $this->_NetsLog = true;
        if (!$oOrderOverview) {
            $this->oOrderOverview = $this;
        } else {
            $this->oOrderOverview = $oOrderOverview;
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
            $data = $this->oOrderOverview->getOrderItems($oxoder_id, false);
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
        $allStatus = $this->oOrderOverview->getPaymentStatus($response, $oxoder_id);
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

    /**
     * Function to capture nets transaction - calls Charge API
     * @return array
     */
    public function getOrderCharged()
    {
        $oxorder = \oxRegistry::getConfig()->getRequestParameter('oxorderid');
        $orderno = \oxRegistry::getConfig()->getRequestParameter('orderno');
        $data = $this->oOrderOverview->getOrderItems($oxorder);
        $payment_id = $this->oCommonHelper->getPaymentId($oxorder);
        // call charge api here
        $chargeUrl = $this->oCommonHelper->getChargePaymentUrl($payment_id);
        $ref = \oxRegistry::getConfig()->getRequestParameter('reference');
        $chargeQty = \oxRegistry::getConfig()->getRequestParameter('charge');
        if (isset($ref) && isset($chargeQty)) {
            $totalAmount = 0;
            foreach ($data['items'] as $key => $value) {
                if (in_array($ref, $value) && $ref === $value['reference']) {
                    $value = $this->oOrderOverview->getValueItem($value, $chargeQty);
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
        NetsLog::log($this->_NetsLog, "Nets_Order_Overview" . json_encode($body));
        $api_return = $this->oCommonHelper->getCurlResponse($chargeUrl, 'POST', json_encode($body));
        $response = json_decode($api_return, true);

        NetsLog::log($this->_NetsLog, "Nets_Order_Overview" . $response);
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
        $data = $this->oOrderOverview->getOrderItems($oxorder);

        $oCommonHelper = new CommonHelper();
        $api_return = $oCommonHelper->getCurlResponse($this->oCommonHelper->getApiUrl() . $this->oCommonHelper->getPaymentId($oxorder), 'GET');
        $response = json_decode($api_return, true);

        $chargeResponse = $this->oOrderOverview->getChargeId($oxorder);
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

                NetsLog::log($this->_NetsLog, "Nets_Order_Overview getorder refund" . json_encode($body));
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
                        $body = $this->oOrderOverview->getItemForRefund($ref, $value, $data);

                        $refundUrl = $this->oCommonHelper->getRefundPaymentUrl($key);
                        $this->oCommonHelper->getCurlResponse($refundUrl, 'POST', json_encode($body));
                        NetsLog::log($this->_NetsLog, "Nets_Order_Overview getorder refund" . json_encode($body));

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
     * Function to get order items to pass capture, refund, cancel api
     * @param $oxorder oxid order id alphanumeric
     * @return array order items and amount
     */

    public function getOrderItems($oxorder, $blExcludeCanceled = true)
    {
        $sSelect = "
			SELECT `oxorderarticles`.* FROM `oxorderarticles`
			WHERE `oxorderarticles`.`oxorderid` = '" . $oxorder . "'" . ($blExcludeCanceled ? "
			AND `oxorderarticles`.`oxstorno` != 1 " : " ") . "
			ORDER BY `oxorderarticles`.`oxartid`, `oxorderarticles`.`oxselvariant`, `oxorderarticles`.`oxpersparam`
		";
        // order articles
        $oArticles = oxNew('oxlist');
        $oArticles->init('oxorderarticle');
        $oArticles->selectString($sSelect);
        $totalOrderAmt = 0;
        $items = array();
        foreach ($oArticles as $listitem) {
            $items[] = $this->oOrderOverview->getItemList($listitem);
            $totalOrderAmt += $this->oOrderOverview->prepareAmount($listitem->oxorderarticles__oxbrutprice->rawValue);
        }
        $sSelectOrder = "
			SELECT `oxorder`.* FROM `oxorder`
			WHERE `oxorder`.`oxid` = '" . $oxorder . "'" . ($blExcludeCanceled ? "
			AND `oxorder`.`oxstorno` != 1 " : " ") . "
			ORDER BY `oxorder`.`oxordernr`";
        $oOrderItems = oxNew('oxlist');
        $oOrderItems->init('oxorder');
        $oOrderItems->selectString($sSelectOrder);
        foreach ($oOrderItems as $item) {
            // payment costs if any additional sent as item
            if ($item->oxorder__oxpaycost->rawValue > 0) {
                $items[] = $this->oOrderOverview->getPayCost($item);
                $totalOrderAmt += $this->oOrderOverview->prepareAmount($item->oxorder__oxpaycost->rawValue);
            }
            // greeting card if sent as item
            if ($item->oxorder__oxgiftcardcost->rawValue > 0) {
                $items[] = $this->oOrderOverview->getGreetingCardItem($item);
                $totalOrderAmt += $this->oOrderOverview->prepareAmount($item->oxorder__oxgiftcardcost->rawValue);
            }
            // gift wrapping if sent as item
            if ($item->oxorder__oxwrapcost->rawValue > 0) {
                $items[] = $this->oOrderOverview->getGiftWrappingItem($item);
                $totalOrderAmt += $this->oOrderOverview->prepareAmount($item->oxorder__oxwrapcost->rawValue);
            }
            // shipping cost if sent as item
            if ($item->oxorder__oxdelcost->rawValue > 0) {
                $items[] = $this->oOrderOverview->getShippingCost($item);
                $totalOrderAmt += $this->prepareAmount($item->oxorder__oxdelcost->rawValue);
            }
        }
        return array(
            "items" => $items,
            "totalAmt" => $totalOrderAmt
        );
    }

    /*
     * Function to get product item listing
     * @return array
     */

    public function getItemList($listitem)
    {
        return [
            'reference' => $listitem->oxorderarticles__oxartnum->value,
            'name' => $listitem->oxorderarticles__oxtitle->value,
            'quantity' => $listitem->oxorderarticles__oxamount->rawValue,
            'unit' => 'pcs',
            'taxRate' => $this->prepareAmount($listitem->oxorderarticles__oxvat->rawValue),
            'unitPrice' => $this->prepareAmount($listitem->oxorderarticles__oxnprice->rawValue),
            'taxAmount' => $this->prepareAmount($listitem->oxorderarticles__oxvatprice->rawValue),
            'grossTotalAmount' => $this->prepareAmount($listitem->oxorderarticles__oxbrutprice->rawValue),
            'netTotalAmount' => $this->prepareAmount($listitem->oxorderarticles__oxnetprice->rawValue),
            'oxbprice' => $listitem->oxorderarticles__oxbprice->rawValue
        ];
    }

    /*
     * Function to get shopping cost
     * @return array
     */

    public function getShoppingCost($item)
    {
        return [
            'reference' => 'shipping',
            'name' => 'shipping',
            'quantity' => 1,
            'unit' => 'units',
            'unitPrice' => $this->prepareAmount($item->oxorder__oxdelcost->rawValue),
            'taxRate' => $this->prepareAmount($item->oxorder__oxdelvat->rawValue),
            'taxAmount' => 0,
            'grossTotalAmount' => $this->prepareAmount($item->oxorder__oxdelcost->rawValue),
            'netTotalAmount' => $this->prepareAmount($item->oxorder__oxdelcost->rawValue),
            'oxbprice' => $item->oxorder__oxdelcost->rawValue
        ];
    }

    /*
     * Function to Get card item
     * @return array
     */

    public function getGreetingCardItem($item)
    {
        return [
            'reference' => 'Greeting Card',
            'name' => 'Greeting Card',
            'quantity' => 1,
            'unit' => 'units',
            'unitPrice' => $this->prepareAmount($item->oxorder__oxgiftcardcost->rawValue),
            'taxRate' => $this->prepareAmount($item->oxorder__oxgiftcardvat->rawValue),
            'taxAmount' => 0,
            'grossTotalAmount' => $this->prepareAmount($item->oxorder__oxgiftcardcost->rawValue),
            'netTotalAmount' => $this->prepareAmount($item->oxorder__oxgiftcardcost->rawValue),
            'oxbprice' => $item->oxorder__oxgiftcardcost->rawValue
        ];
    }

    /*
     * Function to Get Gift wrapping item
     * @return array
     */

    public function getGiftWrappingItem($item)
    {
        return [
            'reference' => 'Gift Wrapping',
            'name' => 'Gift Wrapping',
            'quantity' => 1,
            'unit' => 'units',
            'unitPrice' => $this->prepareAmount($item->oxorder__oxwrapcost->rawValue),
            'taxRate' => $this->prepareAmount($item->oxorder__oxwrapvat->rawValue),
            'taxAmount' => 0,
            'grossTotalAmount' => $this->prepareAmount($item->oxorder__oxwrapcost->rawValue),
            'netTotalAmount' => $this->prepareAmount($item->oxorder__oxwrapcost->rawValue),
            'oxbprice' => $item->oxorder__oxwrapcost->rawValue
        ];
    }

    /*
     * Function to get additional payment cost associated with order item if any
     * @return array
     */

    public function getPayCost($item)
    {
        return [
            'reference' => 'payment costs',
            'name' => 'payment costs',
            'quantity' => 1,
            'unit' => 'units',
            'unitPrice' => $this->prepareAmount($item->oxorder__oxpaycost->rawValue),
            'taxRate' => $this->prepareAmount($item->oxorder__oxpayvat->rawValue),
            'taxAmount' => 0,
            'grossTotalAmount' => $this->prepareAmount($item->oxorder__oxpaycost->rawValue),
            'netTotalAmount' => $this->prepareAmount($item->oxorder__oxpaycost->rawValue),
            'oxbprice' => $item->oxorder__oxpaycost->rawValue
        ];
    }

    /*
     * Function to fetch payment method type from databse table oxorder
     * @param $oxorder_id
     * @return payment method
     */

    public function getPaymentMethod($oxoder_id)
    {
        $oDB = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(true);
        $sSQL_select = "SELECT OXPAYMENTTYPE FROM oxorder WHERE oxid = ? LIMIT 1";
        $payMethod = $oDB->getOne($sSQL_select, [
            $oxoder_id
        ]);
        return $payMethod;
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

    /*
     * Function to prepare amount
     * @return int
     */

    public function prepareAmount($amount = 0)
    {
        return (int) round($amount * 100);
    }

}
