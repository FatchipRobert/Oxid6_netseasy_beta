<?php

namespace Es\NetsEasy\Application\Model\ResourceModel;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

class NetsTransactions
{
    const STATUS_FAILED = 0;
    const STATUS_CANCELLED = 1;
    const STATUS_AUTHORIZED = 2;
    const STATUS_PARTIALLY_CHARGED = 3;
    const STATUS_CHARGED = 4;
    const STATUS_PARTIALLY_REFUNDED = 5;
    const STATUS_REFUNDED = 6;

    protected static $sTableName = "nets_transactions";

    /**
     * Returns a new QueryBuilder object
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected static function getQueryBuilder()
    {
        return ContainerFactory::getInstance()->getContainer()->get(QueryBuilderFactoryInterface::class)->create();
    }

    /**
     * Function to create transaction id in db
     *
     * @param  array  $aRequestData
     * @param  array  $aResponseData
     * @param  string $sOrderId
     * @param  string $sPaymentId
     * @param  int    $iAmount
     * @param  string $sOrderNr
     * @return void
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    public function createEntry($aRequestData, $aResponseData, $sOrderId, $sPaymentId, $iAmount, $sOrderNr = '')
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->insert(self::$sTableName)
            ->values([
                'req_data' => '?',
                'ret_data' => '?',
                'transaction_id' => '?',
                'oxordernr' => '?',
                'oxorder_id' => '?',
                'amount' => '?',
                'created' => '?',
            ])
            ->setParameter(0, json_encode($aRequestData))
            ->setParameter(1, json_encode($aResponseData))
            ->setParameter(2, $sPaymentId)
            ->setParameter(3, $sOrderNr)
            ->setParameter(4, $sOrderId)
            ->setParameter(5, $iAmount)
            ->setParameter(6, date('Y-m-d'));
        return $queryBuilder->execute();
    }

    /**
     * Creates a new charge in the table
     *
     * @param  string $sTransactionId
     * @param  string $sChargeId
     * @param  string $sReference
     * @param  int $iChargeQty
     * @param  int $iChargeQtyLeft
     * @return \Doctrine\DBAL\ForwardCompatibility\Result|int|string
     */
    public function createCharge($sTransactionId, $sChargeId, $sReference, $iChargeQty, $iChargeQtyLeft)
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->insert(self::$sTableName)
            ->values([
                'transaction_id' => ':transactionId',
                'charge_id' => ':chargeId',
                'product_ref' => ':productRef',
                'charge_qty' => ':chargeQty',
                'charge_left_qty' => ':chargeLeftQty',
            ])
            ->setParameter('transactionId', $sTransactionId)
            ->setParameter('chargeId', $sChargeId)
            ->setParameter('productRef', $sReference)
            ->setParameter('chargeQty', $iChargeQty)
            ->setParameter('chargeLeftQty', $iChargeQtyLeft);
        return $queryBuilder->execute();
    }

    /**
     * Returns the transaction_id from nets_transaction table by given order id
     *
     * @param  string $sOrderId
     * @return string
     */
    public static function getTransactionIdByOrderId($sOrderId)
    {
        $queryBuilder = self::getQueryBuilder();
        $queryBuilder
            ->select('transaction_id')
            ->from(self::$sTableName)
            ->where('oxorder_id = :orderId')
            ->setParameter('orderId', $sOrderId);
        return $queryBuilder->execute()->fetchOne();
    }

    /**
     * Updates transaction entry with finished order info
     *
     * @param  string $sPaymentId
     * @param  string $sOrderId
     * @param  string $sOrderNr
     * @return \Doctrine\DBAL\ForwardCompatibility\Result|int|string
     */
    public function updateTransactionWithOrderInfo($sPaymentId, $sOrderId, $sOrderNr)
    {
        $queryBuilder = $this->getQueryBuilder($sPaymentId, $sOrderId, $sOrderNr);
        $queryBuilder->update(self::$sTableName, 'o')
            ->set('o.oxordernr', ':orderNr')
            ->set('o.hash', ':hash')
            ->set('o.oxorder_id', ':orderId')
            ->where('o.transaction_id = :transactionId')
            ->setParameter('orderNr', $sOrderNr)
            ->setParameter('hash', $sOrderId)
            ->setParameter('orderId', $sOrderId)
            ->setParameter('transactionId', $sPaymentId);
        return $queryBuilder->execute();
    }

    /**
     * Updates the payment status in the transactions db table
     *
     * @param int $iStatus
     * @param string $sOrderId
     * @return \Doctrine\DBAL\ForwardCompatibility\Result|int|string
     */
    public function updatePaymentStatus($iStatus, $sOrderId)
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->update(self::$sTableName, 'o')
            ->set('o.payment_status', ':paymentStatus')
            ->where('o.oxorder_id = :orderId')
            ->setParameter('paymentStatus', $iStatus)
            ->setParameter('orderId', $sOrderId);
        return $queryBuilder->execute();
    }

    /**
     * Updates the charge_qty_left field
     *
     * @param  int $iChargeQtyLeft
     * @param  string $sTransactionId
     * @param  string $sChargeId
     * @param  string $sProductRef
     * @return \Doctrine\DBAL\ForwardCompatibility\Result|int|string
     */
    public function updateChargeQtyLeft($iChargeQtyLeft, $sTransactionId, $sChargeId, $sProductRef = false)
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->update(self::$sTableName, 'o')
            ->set('o.charge_left_qty', ':chargeQtyLeft')
            ->where('o.transaction_id = :transactionId')
            ->andWhere('o.charge_id = :chargeId')
            ->setParameter('chargeQtyLeft', $iChargeQtyLeft)
            ->setParameter('transactionId', $sTransactionId)
            ->setParameter('chargeId', $sChargeId);
        if (!empty($sProductRef)) {
            $queryBuilder->andWhere('o.product_ref = :productRef')
                ->setParameter('productRef', $sProductRef);
        }
        return $queryBuilder->execute();
    }

    /**
     * @param $sPartialAmount
     * @param $sChargeId
     * @param $sOrderId
     * @return \Doctrine\DBAL\ForwardCompatibility\Result|int|string
     */
    public function updatePartialAmountAndChargeId($sPartialAmount, $sChargeId, $sOrderId)
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->update(self::$sTableName, 'o')
            ->set('o.partial_amount', ':partialAmount')
            ->set('o.charge_id', ':chargeId')
            ->where('o.oxorder_id = :orderId')
            ->setParameter('partialAmount', $sPartialAmount)
            ->setParameter('chargeId', $sChargeId)
            ->setParameter('orderId', $sOrderId);
        return $queryBuilder->execute();
    }

    /**
     * Returns payment status by given order id
     *
     * @param  string $sOrderId
     * @return int
     */
    public function getPaymentStatusByOrderId($sOrderId)
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->select('payment_status')
            ->from(self::$sTableName)
            ->where('oxorder_id = :orderId')
            ->setParameter('orderId', $sOrderId);
        return $queryBuilder->execute()->fetchOne();
    }

    /**
     * Returns charge info
     *
     * @param  string $sTransactionId
     * @param  string $sChargeId
     * @param  string $sProductRef
     * @return array
     */
    public function getChargeInfo($sTransactionId, $sChargeId, $sProductRef)
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->select('transaction_id', 'charge_id', 'product_ref', 'charge_qty', 'charge_left_qty')
            ->from(self::$sTableName)
            ->where('transaction_id = :transactionId')
            ->andWhere('charge_id = :chargeId')
            ->andWhere('product_ref = :productRef')
            ->andWhere('charge_left_qty != :chargeLeftQty')
            ->setParameter('transactionId', $sTransactionId)
            ->setParameter('chargeId', $sChargeId)
            ->setParameter('productRef', $sProductRef)
            ->setParameter('chargeLeftQty', 0); // not 0
        return $queryBuilder->execute()->fetchAll();
    }
}