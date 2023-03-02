<?php

namespace Es\NetsEasy\extend\Application\Model;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Field;
use Es\NetsEasy\Application\Model\Api\Payment\ChargeRefund;
use Es\NetsEasy\Application\Model\Api\Payment\PaymentCancel;
use Es\NetsEasy\Application\Model\Api\Payment\PaymentCharge;
use Es\NetsEasy\Application\Model\Api\Payment\PaymentCreate;
use Es\NetsEasy\Application\Model\Api\Payment\PaymentRetrieve;
use Es\NetsEasy\Application\Model\ResourceModel\NetsTransactions;
use Es\NetsEasy\Application\Helper\Payment;

class Order extends Order_parent
{
    protected $blIsNetsHostedModeReturn = false;

    protected $netsRecalculateOrderMode = null;

    protected $netsIsFinalizeDummy = false;

    /**
     * @return bool
     */
    public function netsGetIsFinalizeDummy()
    {
        return $this->netsIsFinalizeDummy;
    }

    /**
     * @param  bool $blIsFinalizeDummy
     * @return void
     */
    public function netsSetIsFinalizeDummy($blIsFinalizeDummy)
    {
        $this->netsIsFinalizeDummy = $blIsFinalizeDummy;
    }

    /**
     * Returns if order was payed with a Nets payment type
     *
     * @return bool
     */
    public function netsIsNetsPaymentUsed()
    {
        return Payment::getInstance()->isNetsPayment($this->oxorder__oxpaymenttype->value);
    }

    /**
     * Determines if this is a hosted mode return call
     *
     * @return bool
     */
    protected function netsIsHostedModeReturn()
    {
        if (Registry::getRequest()->getRequestEscapedParameter('fnc') == 'netsReturnHosted') {
            return true;
        }
        return false;
    }

    /**
     * Order number has to be set before payment to have it for API communication
     * The original method is protected so this is used to make it usable anyways
     *
     * @return void
     */
    public function netsSetOrderNumber()
    {
        if (!$this->oxorder__oxordernr->value) {
            $this->_setNumber();
        }
    }

    /**
     * Save nets payment id in order object
     *
     * @param  string $sTransactionId
     * @return void
     */
    public function netsSetTransactionId($sTransactionId)
    {
        $oQueryBuilder = ContainerFactory::getInstance()->getContainer()->get(QueryBuilderFactoryInterface::class)->create();
        $oQueryBuilder->update('oxorder')
            ->set('oxtransid', ':transId')
            ->where('oxid = :orderId')
            ->setParameter('transId', $sTransactionId)
            ->setParameter('orderId', $this->getId());
        $oQueryBuilder->execute();

        $this->oxorder__oxtransid = new Field($sTransactionId);
    }

    /**
     * Mark order as paid
     *
     * @return void
     */
    public function netsMarkAsPaid($sDate = false)
    {
        if (strlen($sDate) == 10) { // only a date given as parameter
            $sDate = date('Y-m-d H:i:s', strtotime($sDate));
        }

        if ($sDate === false) {
            $sDate = date('Y-m-d H:i:s');
        }

        $oQueryBuilder = ContainerFactory::getInstance()->getContainer()->get(QueryBuilderFactoryInterface::class)->create();
        $oQueryBuilder->update('oxorder')
            ->set('oxpaid', ':paidDate')
            ->where('oxid = :orderId')
            ->setParameter('paidDate', $sDate)
            ->setParameter('orderId', $this->getId());
        $oQueryBuilder->execute();

        $this->oxorder__oxpaid = new Field($sDate);
    }

    /**
     * Creates a new payment with the Nets API and creates a new transaction entry in the nets_transactions db table
     *
     * @return array
     */
    public function netsCreateNewNetsPayment()
    {
        $oCreatePayment = oxNew(PaymentCreate::class);
        $aResponse = $oCreatePayment->sendRequest($this);

        if (!empty($aResponse['paymentId']) && $this->netsGetIsFinalizeDummy() === false) {
            $this->netsSetTransactionId($aResponse['paymentId']);
        }

        $aRequestParams = $oCreatePayment->getRequestParameters();

        $oTransactionModel = oxNew(NetsTransactions::class);
        $oTransactionModel->createEntry($aRequestParams, $aResponse, $this->getId(), $aResponse['paymentId'], $aRequestParams['order']['amount'], $this->oxorder__oxordernr->value);

        return $aResponse;
    }

    /**
     * @return void
     */
    public function netsCaptureOrder()
    {
        $oPaymentCharge = oxNew(PaymentCharge::class);
        $aResponse = $oPaymentCharge->sendRequest($this);

        if (!empty($aResponse['chargeId'])) {
            $this->netsMarkAsPaid();

            // save charge details in db for refund
            $oNetsTransaction = oxNew(NetsTransactions::class);

            $aRequestParams = $oPaymentCharge->getRequestParameters();
            foreach ($aRequestParams['orderItems'] as $aItem) {
                $oNetsTransaction->createCharge($this->oxorder__oxtransid->value, $aResponse['chargeId'], $aItem['reference'], $aItem['quantity'], $aItem['quantity']);
            }
        }
    }

    /**
     * Sends partial capture to API via item reference and quantity
     *
     * @param  string $sCaptureReference
     * @param  int $iQuantity
     * @return void
     */
    public function netsCaptureByReference($sCaptureReference, $iQuantity)
    {
        $oPaymentCharge = oxNew(PaymentCharge::class);
        $aResponse = $oPaymentCharge->sendRequest($this, $sCaptureReference, $iQuantity);

        if (!empty($aResponse['chargeId'])) {
            // Should the order really be set to paid in a partial capture??? Behavior taken from old implementation
            $this->netsMarkAsPaid();

            // save charge details in db for partial refund
            $oNetsTransaction = oxNew(NetsTransactions::class);
            $oNetsTransaction->createCharge($this->oxorder__oxtransid->value, $aResponse['chargeId'], $sCaptureReference, $iQuantity, $iQuantity);
        }
    }

    /**
     * @return void
     */
    public function netsRefundOrder()
    {
        $oPaymentInfo = oxNew(PaymentRetrieve::class);
        $aResponse = $oPaymentInfo->sendRequest($this->oxorder__oxtransid->value);

        foreach ($aResponse['payment']['charges'] as $aCharge) {
            $oChargeRefund = oxNew(ChargeRefund::class);
            $oChargeRefund->sendRequestByCharge($aCharge);

            $oNetsTransactions = oxNew(NetsTransactions::class);
            $oNetsTransactions->updateChargeQtyLeft(0, $this->oxorder__oxtransid->value, $aCharge['chargeId']);
        }
    }

    /**
     * @param  string $sRefundReference
     * @param  int $iRefundQuantity
     * @return void
     */
    public function netsRefundByReference($sRefundReference, $iRefundQuantity)
    {
        $oNetsTransactions = oxNew(NetsTransactions::class);
        $aRefundQuantities = [];
        $blBreakloop = false;

        $oPaymentInfo = oxNew(PaymentRetrieve::class);
        $aResponse = $oPaymentInfo->sendRequest($this->oxorder__oxtransid->value);
        foreach ($aResponse['payment']['charges'] as $aCharge) {
            if (in_array($sRefundReference, array_column($aCharge['orderItems'], 'reference'))) {
                $aChargeInfo = $oNetsTransactions->getChargeInfo($this->oxorder__oxtransid->value, $aCharge['chargeId'], $sRefundReference);
                if (count($aChargeInfo) > 0) {
                    $iChargeLeftQuantity = $aChargeInfo[0]['charge_left_qty'];
                    $aRefundQuantities[$aCharge['chargeId']] = $aChargeInfo[0]['charge_left_qty'];
                }
                if ($iRefundQuantity <= array_sum($aRefundQuantities)) {
                    $iQuantityLeftFromArray = array_sum($aRefundQuantities) - $iRefundQuantity;
                    $iQuantityLeft = $iChargeLeftQuantity - $iQuantityLeftFromArray;
                    $aRefundQuantities[$aCharge['chargeId']] = $iQuantityLeft;
                    $blBreakloop = true;
                }
                if ($blBreakloop) {
                    foreach ($aRefundQuantities as $sChargeId => $iQuantity) {
                        $oChargeRefund = oxNew(ChargeRefund::class);
                        $oChargeRefund->sendRequestByReference($this, $sChargeId, $sRefundReference, $iQuantity);

                        $aChargeInfo = $oNetsTransactions->getChargeInfo($this->oxorder__oxtransid->value, $aCharge['chargeId'], $sRefundReference);
                        if (count($aChargeInfo) > 0) {
                            $iChargeLeftQuantity = $aChargeInfo[0]['charge_left_qty'];
                        }
                        $iChargeLeftQuantity = $iQuantity - $iChargeLeftQuantity;
                        if ($iChargeLeftQuantity < 0) {
                            $iChargeLeftQuantity = - $iChargeLeftQuantity;
                        }

                        $oNetsTransactions->updateChargeQtyLeft($iChargeLeftQuantity, $this->oxorder__oxtransid->value, $sChargeId, $sRefundReference);
                    }
                    break;
                }
            }
        }
    }

    /**
     * Sends a cancel request to the API
     *
     * @return void
     */
    public function netsCancelOrder()
    {
        $oPaymentCancel = oxNew(PaymentCancel::class);
        $oPaymentCancel->sendRequest($this);

        $this->cancelOrder();
    }

    /**
     * This method includes some of the tasks the full finalizeOrder method does
     * But there is no order created, only the basket data is transfered to the order object
     * This is needed for the embedded mode, since a payment/transaction has to be created before the order is generated
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket
     * @param \OxidEsales\Eshop\Application\Model\User   $oUser
     * @return void
     */
    public function netsPrepareDummyOrderForEmbeddedPayment(\OxidEsales\Eshop\Application\Model\Basket $oBasket, $oUser)
    {
        $this->netsSetIsFinalizeDummy(true);

        // check if this order is already stored
        $orderId = \OxidEsales\Eshop\Core\Registry::getSession()->getVariable('sess_challenge');

        // use this ID
        $this->setId($orderId);

        // copies user info
        $this->_setUser($oUser);

        // copies basket info
        $this->_loadFromBasket($oBasket);
    }

    /**
     * Order checking, processing and saving method.
     * Before saving performed checking if order is still not executed (checks in
     * database oxorder table for order with know ID), if yes - returns error code 3,
     * if not - loads payment data, assigns all info from basket to new Order object
     * and saves full order with error status. Then executes payment. On failure -
     * deletes order and returns error code 2. On success - saves order (\OxidEsales\Eshop\Application\Model\Order::save()),
     * removes article from wishlist (\OxidEsales\Eshop\Application\Model\Order::_updateWishlist()), updates voucher data
     * (\OxidEsales\Eshop\Application\Model\Order::_markVouchers()). Finally sends order confirmation email to customer
     * (\OxidEsales\Eshop\Core\Email::SendOrderEMailToUser()) and shop owner (\OxidEsales\Eshop\Core\Email::SendOrderEMailToOwner()).
     * If this is order recalculation, skipping payment execution, marking vouchers as used
     * and sending order by email to shop owner and user
     * Mailing status (1 if OK, 0 on error) is returned.
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket              Basket object
     * @param object                                     $oUser                Current User object
     * @param bool                                       $blRecalculatingOrder Order recalculation
     * @return integer
     */
    public function finalizeOrder(\OxidEsales\Eshop\Application\Model\Basket $oBasket, $oUser, $blRecalculatingOrder = false)
    {
        $this->netsRecalculateOrderMode = $blRecalculatingOrder;
        if (Payment::getInstance()->isNetsPayment($oBasket->getPaymentId()) === true && $this->netsIsHostedModeReturn() === true) {
            $this->blIsNetsHostedModeReturn = true;
        }

        $iReturn = parent::finalizeOrder($oBasket, $oUser, $blRecalculatingOrder);
        if (in_array($iReturn, [self::ORDER_STATE_OK, self::ORDER_STATE_MAILINGERROR])) { // finalize was successful
            if (Registry::getConfig()->getConfigParam('nets_autocapture')) {
                $this->netsMarkAsPaid();
            }
            $this->netsClearSessionFlags();
        }
        return $iReturn;
    }

    /**
     * Clears nets session helper variables
     *
     * @return void
     */
    protected function netsClearSessionFlags()
    {
        Registry::getSession()->deleteVariable("nets_embedded_payment_id");
    }

    /**
     * Checking if this order is already stored.
     *
     * @param string $sOxId order ID
     * @return bool
     */
    protected function _checkOrderExist($sOxId = null) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->blIsNetsHostedModeReturn === false) {
            return parent::_checkOrderExist($sOxId);
        }
        return false; // In hosted mode the parent finalizeOrder method is run again so the order DOES exist, but thats ok
    }

    /**
     * Function which checks if article stock is valid.
     * If not displays error and returns false.
     *
     * @param object $oBasket basket object
     *
     * @throws \OxidEsales\Eshop\Core\Exception\NoArticleException
     * @throws \OxidEsales\Eshop\Core\Exception\ArticleInputException
     * @throws \OxidEsales\Eshop\Core\Exception\OutOfStockException
     */
    public function validateStock($oBasket)
    {
        if ($this->blIsNetsHostedModeReturn === false) {
            return parent::validateStock($oBasket);
        }
    }

    /**
     * Gathers and assigns to new oxOrder object customer data, payment, delivery
     * and shipping info, customer order remark, currency, voucher, language data.
     * Additionally stores general discount and wrapping. Sets order status to "error"
     * and creates oxOrderArticle objects and assigns to them basket articles.
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket Shopping basket object
     */
    protected function _loadFromBasket(\OxidEsales\Eshop\Application\Model\Basket $oBasket) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->blIsNetsHostedModeReturn === false) {
            return parent::_loadFromBasket($oBasket);
        }
        $this->load(Registry::getSession()->getVariable('sess_challenge'));
    }

    /**
     * Creates and returns user payment.
     *
     * @param string $sPaymentid used payment id
     * @return \OxidEsales\Eshop\Application\Model\UserPayment
     */
    protected function _setPayment($sPaymentid) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->blIsNetsHostedModeReturn === false) {
            return parent::_setPayment($sPaymentid);
        }
        $oUserPayment = oxNew(\OxidEsales\Eshop\Application\Model\UserPayment::class);
        $oUserPayment->load($this->oxorder__oxpaymentid->value);
        return $oUserPayment;
    }

    /**
     * Updates order transaction status. Faster than saving whole object
     *
     * @param string $sStatus order transaction status
     */
    protected function _setOrderStatus($sStatus) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->mollieRecalculateOrder === true && $this->oxorder__oxtransstatus->value == "NOT_FINISHED" && $this->netsIsNetsPaymentUsed()) {
            return;
        }
        parent::_setOrderStatus($sStatus);
    }

    /**
     * Executes payment. Additionally loads oxPaymentGateway object, initiates
     * it by adding payment parameters (oxPaymentGateway::setPaymentParams())
     * and finally executes it (oxPaymentGateway::executePayment()). On failure -
     * deletes order and returns * error code 2.
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket      basket object
     * @param object                                     $oUserpayment user payment object
     * @return  integer 2 or an error code
     */
    protected function _executePayment(\OxidEsales\Eshop\Application\Model\Basket $oBasket, $oUserpayment) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if ($this->blIsNetsHostedModeReturn === false) {
            return parent::_executePayment($oBasket, $oUserpayment);
        }
        return true;
    }
}
