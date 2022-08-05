<?php

namespace Es\NetsEasy\ShopExtend\Application\Models;

/**
 * Class defines Nets payment operations in order view
 */
class OrderOverview
{
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
     * Function to prepare amount
     * @return int
     */

    public function prepareAmount($amount = 0)
    {
        return (int) round($amount * 100);
    }

}
