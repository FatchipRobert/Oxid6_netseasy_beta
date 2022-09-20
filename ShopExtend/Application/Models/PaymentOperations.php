<?php

namespace Es\NetsEasy\ShopExtend\Application\Models;

use Es\NetsEasy\Api\NetsPaymentTypes;
use Es\NetsEasy\Core\CommonHelper;
use Es\NetsEasy\ShopExtend\Application\Models\OrderItems;
use OxidEsales\EshopCommunity\Core\Request;
use \OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use \OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use Es\NetsEasy\Core\DebugHandler;

/**
 * Class defines Nets payment operations in order view
 */
class PaymentOperations
{

    protected $oCommonHelper;
    protected $paymentOPObject;
    protected $oDebugHandler;
    protected $oOrderItems;
    protected $queryBuilder;

    /**
     * Constructor
     * @param object $paymentOPObject The OXID Payment model
     * @param object $commonHelper The service file injected as object
     * @param array $oOrderItems The OXID Order items array
     * @return Null
     */
    public function __construct($paymentOPObject = null, CommonHelper $commonHelper, $oOrderItems = null)
    {
        $this->oDebugHandler = \oxNew(DebugHandler::class);
        $this->queryBuilder = ContainerFactory::getInstance()
                ->getContainer()
                ->get(QueryBuilderFactoryInterface::class);
        if (!$paymentOPObject) {
            $this->paymentOPObject = $this;
        } else {
            $this->paymentOPObject = $paymentOPObject;
        }
        // works only if StaticHelper is not autoloaded yet!
        $this->oCommonHelper = $commonHelper;

        if (!$oOrderItems) {
            $this->oOrderItems = \oxNew(OrderItems::class, null, $this->oCommonHelper);
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
        $oxorder = Request::getRequestParameter('oxorderid');
        $orderno = Request::getRequestParameter('orderno');
        $data = $this->oOrderItems->getOrderItems($oxorder);
        $payment_id = $this->oCommonHelper->getPaymentId($oxorder);
        // call charge api here
        $chargeUrl = $this->oCommonHelper->getChargePaymentUrl($payment_id);
        $ref = Request::getRequestParameter('reference');
        $chargeQty = Request::getRequestParameter('charge');
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
		
        $this->oDebugHandler->log("Nets_Order_Overview" . json_encode($body));
        $api_return = $this->oCommonHelper->getCurlResponse($chargeUrl, 'POST', json_encode($body));
        $response = json_decode($api_return, true);

        $this->oDebugHandler->log("Nets_Order_Overview" . $response);
        $dt = date("Y-m-d H:i:s");
        $queryBuilder = $this->queryBuilder->create();
        $queryBuilder
                ->update('oxorder', 'o')
                ->set('o.oxpaid', '?')
                ->where('o.oxid = ?')
                ->setParameter(0, "'{$dt}'")
                ->setParameter(1, "'{$oxorder}'")->execute();
        // save charge details in db for partial refund
        if (isset($ref) && isset($response['chargeId'])) {
            $queryBuilder = $this->queryBuilder->create();
            $queryBuilder->insert('oxnets')
                    ->values(
                            array(
                                'transaction_id' => '?',
                                'charge_id' => '?',
                                'product_ref' => '?',
                                'charge_qty' => '?',
                                'charge_left_qty' => '?'
                            )
                    )
                    ->setParameter(0, $payment_id)
                    ->setParameter(1, $response['chargeId'])
                    ->setParameter(2, $ref)
                    ->setParameter(3, $chargeQty)
                    ->setParameter(4, $chargeQty)->execute();
        } else {
            if (isset($response['chargeId'])) {
                foreach ($data['items'] as $key => $value) {
                    $queryBuilder = $this->queryBuilder->create();
                    $queryBuilder->insert('oxnets')
                            ->values(
                                    array(
                                        'transaction_id' => '?',
                                        'charge_id' => '?',
                                        'product_ref' => '?',
                                        'charge_qty' => '?',
                                        'charge_left_qty' => '?'
                                    )
                            )
                            ->setParameter(0, $payment_id)
                            ->setParameter(1, $response['chargeId'])
                            ->setParameter(2, $value['reference'])
                            ->setParameter(3, $value['quantity'])
                            ->setParameter(4, $value['quantity'])->execute();
                }
            }
        }
        return true;
    }

    /*
     * Function to get value item list for charge
     * @param array $value The OXID Order item
     * @param int The OXID Order quantity to be charged
     * return array
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
     * @return null
     */

    public function getOrderRefund()
    {
        $oxorder = Request::getRequestParameter('oxorderid');
        $orderno = Request::getRequestParameter('orderno');
        $data = $this->oOrderItems->getOrderItems($oxorder);
		$payment_id = $this->oCommonHelper->getPaymentId($oxorder);

        $oCommonHelper = new CommonHelper();
        $api_return = $oCommonHelper->getCurlResponse($this->oCommonHelper->getApiUrl() . $payment_id, 'GET');
        $response = json_decode($api_return, true);

        $chargeResponse = $this->paymentOPObject->getChargeId($oxorder);
        $ref = Request::getRequestParameter('reference');
        $refundQty = Request::getRequestParameter('refund');
        
        $refundEachQtyArr = [];
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
                $queryBuilder = $this->queryBuilder->create();
                $queryBuilder
                        ->update('oxnets', 'o')
                        ->set('o.charge_left_qty', '?')
                        ->where('o.transaction_id = ?')
                        ->andWhere('o.charge_id = ?')
                        ->setParameter(0, 0)->setParameter(1, $payment_id)->setParameter(2, $val['chargeId'])->execute();

                $this->oDebugHandler->log("Nets_Order_Overview getorder refund" . json_encode($body));
            } else if (in_array($ref, array_column($val['orderItems'], 'reference'))) {
                $queryBuilder = $this->queryBuilder->create();
                $queryBuilder
                        ->select('transaction_id', 'charge_id', 'product_ref', 'charge_qty', 'charge_left_qty')
                        ->from('oxnets')
                        ->where('transaction_id = ?')
                        ->andWhere('charge_id = ?')
                        ->andWhere('product_ref = ?')
                        ->andWhere('charge_left_qty != ?')
                        ->setParameter(0, $payment_id)->setParameter(1, $val['chargeId'])->setParameter(2, $ref)->setParameter(3, 0);
                $charge_query = $queryBuilder->execute()->fetchAll();

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
                        $this->oDebugHandler->log("Nets_Order_Overview getorder refund" . json_encode($body));

                        $queryBuilder = $this->queryBuilder->create();
                        $queryBuilder
                                ->select('charge_left_qty')
                                ->from('oxnets')
                                ->where('transaction_id = ?')
                                ->andWhere('charge_id = ?')
                                ->andWhere('product_ref = ?')
                                ->andWhere('charge_left_qty != ?')
                                ->setParameter(0, $payment_id)->setParameter(1, $val['chargeId'])->setParameter(2, $ref)->setParameter(3, 0);
                        $charge_query = $queryBuilder->execute()->fetchAll();

                        if (count($singlecharge_query) > 0) {
                            $charge_left_qty = $singlecharge_query[0]['charge_left_qty'];
                        }
                        $charge_left_qty = $value - $charge_left_qty;
                        if ($charge_left_qty < 0) {
                            $charge_left_qty = - $charge_left_qty;
                        }
                        $queryBuilder = $this->queryBuilder->create();
                        $queryBuilder
                                ->update('oxnets', 'o')
                                ->set('o.charge_left_qty', '?')
                                ->where('o.transaction_id = ?')
                                ->andWhere('o.charge_id = ?')
                                ->andWhere('o.product_ref = ?')
                                ->setParameter(0, $charge_left_qty)->setParameter(1, $payment_id)->setParameter(2, $key)->setParameter(3, $ref)->execute();
                    }
                    break;
                }
            }
        }
    }

    /*
     * Function to fetch charge id from databse table oxnets
     * @param string $oxorder_id The order ID
     * @return string charge id
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
            $result = [
                "chargeId" => $response['payment']['charges'][0]['chargeId']
            ];
        } else {
            $result = [
                "chargeId" => $chargesMap
            ];
        }
        $result["response"] = $response;
        return $result;
    }

    /*
     * Function to Get order Items to refund and pass them to refund api
     * @param string $ref The order reference
     * @param int $refundQty The order refund quantity
     * @param array $data The order items array
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
