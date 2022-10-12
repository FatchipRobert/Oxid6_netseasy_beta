<?php

namespace Es\NetsEasy\ShopExtend\Application\Models;

use Es\NetsEasy\Api\NetsPaymentTypes;
use Es\NetsEasy\Api\NetsLog;
use OxidEsales\EshopCommunity\Core\Registry;
use Es\NetsEasy\Core\DebugHandler;
use \OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use \OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;

/**
 * Class defines execution of nets payment.
 */
class PaymentGateway extends PaymentGateway_parent
{

    protected $netsPaymentTypes;

    /**
     * Function to execute Nets payment.
     * @param int $dAmount The order item amount
     * @param object $oOrder The OXID Order object
     * @return bool
     */
    public function executePayment($dAmount, &$oOrder)
    {
        $oxConfig = \oxNew(\OxidEsales\EshopCommunity\Core\Config::class);
        $oxSession = \oxNew(\OxidEsales\EshopCommunity\Core\Session::class);
        $oDebugHandler = \oxNew(DebugHandler::class);
        $this->netsPaymentTypes = \oxNew(NetsPaymentTypes::class);
        $ox_payment_id = $oxSession->getBasket()->getPaymentId();
        $payment_type = $this->netsPaymentTypes->getNetsPaymentType($ox_payment_id);
        $oDebugHandler->log("NetsPaymentGateway executePayment: " . $payment_type);
        if ((!isset($payment_type) || !$payment_type) && $dAmount != 'test') { 
            $oDebugHandler->log("NetsPaymentGateway executePayment, parent");
            return parent::executePayment($dAmount, $oOrder);
        }
        $oDebugHandler->log("NetsPaymentGateway executePayment");
        $success = true;
        $oxSession->deleteVariable('nets_success');
        if (isset($success) && $success === true) {
            $oDebugHandler->log("NetsPaymentGateway executePayment - success");
            return true;
        }
        $oDebugHandler->log("NetsPaymentGateway executePayment - failure");
        return false;
    }

    /**
     * Function to create transaction id in db
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     * @param array $req_data The API request array
     * @param array $ret_data The API return array
     * @param string $hash The Order hash
     * @param string $payment_id The NETS payment ID
     * @param string $oxorder_id The Order ID
     * @param int $amount The order item amount
     * @return Null
     */
    public function createTransactionEntry($req_data, $ret_data, $hash, $payment_id, $oxorder_id, $amount)
    {
        $queryBuilder = ContainerFactory::getInstance()->getContainer()->get(QueryBuilderFactoryInterface::class)->create();
        $queryBuilder->insert('oxnets')
                ->values(
                        array(
                            'req_data' => '?',
                            'ret_data' => '?',
                            'transaction_id' => '?',
                            'oxordernr' => '?',
                            'oxorder_id' => '?',
                            'amount' => '?',
                            'created' => '?'
                        )
                )
                ->setParameter(0, $req_data)
                ->setParameter(1, $ret_data)
                ->setParameter(2, $payment_id)
                ->setParameter(3, $oxorder_id)
                ->setParameter(4, $hash)
                ->setParameter(5, $amount)
                ->setParameter(6, date('Y-m-d'))->execute();
    }

    /**
     * Function to set transaction id in db
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     * @param string $hash The Order hash
     * @param string $transaction_id The NETS transaction ID
     * @param bool $log_error The NETS API errors
     * @return Null
     */
    public function setTransactionId($hash, $transaction_id, $log_error = false)
    {
        if (!empty($hash) & !empty($transaction_id)) {
            $queryBuilder = ContainerFactory::getInstance()->getContainer()->get(QueryBuilderFactoryInterface::class)->create();
            $queryBuilder
                    ->update('oxnets', 'o')
                    ->set('o.transaction_id', '?')
                    ->where('o.transaction_id = ?')
                    ->andWhere('o.hash = ?')
                    ->setParameter(0, $transaction_id)->setParameter(1, null)->setParameter(2, $hash)->execute();
        } else {
            $oDebugHandler = \oxNew(DebugHandler::class);
            $oDebugHandler->log('nets_api, hash or transaction_id empty');
        }
    }

}
