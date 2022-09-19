<?php

namespace Es\NetsEasy\ShopExtend\Application\Models;

use \OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use \OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

/**
 * Class defines Nets payment operations in order view
 */
class OrderOverview
{
    /*
     * Function to fetch payment method type from databse table oxorder
     * @param $oxorder_id
     * @return string payment method
     */

    public function getPaymentMethod($oxoder_id)
    {
        $queryBuilder = ContainerFactory::getInstance()
                        ->getContainer()
                        ->get(QueryBuilderFactoryInterface::class)->create();
        $queryBuilder
                ->select('OXPAYMENTTYPE')
                ->from('oxorder')
                ->where('oxid = :oxorder_id')
                ->setParameters([
                    'oxorder_id' => $oxoder_id,
        ]);
        $payMethod = $queryBuilder->execute()->fetchOne();

        return $payMethod;
    }

    /*
     * Function to get shopping cost
     * @param array $item The order items array
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
     * @param int $amount The order item amount
     * @return int
     */

    public function prepareAmount($amount = 0)
    {
        return (int) round($amount * 100);
    }

    /*
     * Fetch partial amount
     * @param string $oxoder_id The order id
     * @return int
     */

    public function getPartialAmount($oxoder_id)
    {
        $queryBuilder = ContainerFactory::getInstance()
                        ->getContainer()
                        ->get(QueryBuilderFactoryInterface::class)->create();
        $queryBuilder
                ->select('partial_amount')
                ->from('oxnets')
                ->where('oxorder_id = :oxorder_id')
                ->setParameters([
                    'oxorder_id' => $oxoder_id,
        ]);
        $partial_amount = $queryBuilder->execute()->fetchOne();
        return $partial_amount;
    }

}
