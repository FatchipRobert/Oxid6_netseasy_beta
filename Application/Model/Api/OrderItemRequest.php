<?php

namespace Es\NetsEasy\Application\Model\Api;

use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\OrderArticle;
use OxidEsales\Eshop\Core\Price;
use Es\NetsEasy\Application\Helper\Api;

abstract class OrderItemRequest extends BaseRequest
{
    /**
     * @var string|null
     */
    protected $sRequestMethod = self::METHOD_POST;

    /**
     * Returns an item array for the API call
     *
     * @param  string $sArtnum
     * @param  string $sTitle
     * @param  int $iQuantity
     * @param  double $dUnitPrice
     * @param  double $dUnitPriceBrut
     * @param  double $dTotalBrutAmount
     * @param  double $dTotalNetAmount
     * @param  double $dTaxAmount
     * @param  double|false $dTaxRate
     * @param  string $sUnit
     * @return array
     */
    protected function getItemArray($sArtnum, $sTitle, $iQuantity, $dUnitPrice, $dUnitPriceBrut, $dTotalBrutAmount, $dTotalNetAmount, $dTaxAmount, $dTaxRate = false, $sUnit = "units")
    {
        $aItemArray = [
            'reference' => $sArtnum,
            'name' => $sTitle,
            'quantity' => $iQuantity,
            'unit' => $sUnit,
            'unitPrice' =>  Api::getInstance()->formatPrice($dUnitPrice), // net unit price
            'unitPriceBrut' =>  $dUnitPriceBrut, // brut unit price - not expected in API - check behaviour, used for recalculation
            'taxAmount' => Api::getInstance()->formatPrice($dTaxAmount),
            'grossTotalAmount' => Api::getInstance()->formatPrice($dTotalBrutAmount),
            'netTotalAmount' => Api::getInstance()->formatPrice($dTotalNetAmount),
        ];
        if ($dTaxRate !== false) {
            $aItemArray['taxRate'] = $dTaxRate * 100;
        }
        return $aItemArray;
    }

    /**
     * Recalculates order item for given quantity
     *
     * @param  array $aOrderItem
     * @param  int $iQuantity
     * @return array
     */
    protected function recalculateOrderItemForNewQuantity($aOrderItem, $iQuantity)
    {
        if ($aOrderItem['quantity'] == $iQuantity) {
            return $aOrderItem;
        }

        $aOrderItem['grossTotalAmount'] = Api::getInstance()->formatPrice($aOrderItem['unitPriceBrut'] * $iQuantity);
        $aOrderItem['netTotalAmount'] = round($aOrderItem['unitPrice'] * $iQuantity);
        $aOrderItem['taxAmount'] = $aOrderItem['grossTotalAmount'] - $aOrderItem['netTotalAmount'];
        $aOrderItem['quantity'] = $iQuantity;

        return $aOrderItem;
    }

    /**
     * Returns item array by given orderarticle object
     *
     * @param  OrderArticle $oOrderArticle
     * @return array
     */
    protected function getItemArrayByOrderArticle($oOrderArticle)
    {
        return $this->getItemArray(
            $oOrderArticle->oxorderarticles__oxartnum->value,
            $oOrderArticle->oxorderarticles__oxtitle->value,
            $oOrderArticle->oxorderarticles__oxamount->value,
            $oOrderArticle->oxorderarticles__oxnprice->value,
            $oOrderArticle->oxorderarticles__oxbprice->value,
            $oOrderArticle->oxorderarticles__oxbrutprice->value,
            $oOrderArticle->oxorderarticles__oxnetprice->value,
            $oOrderArticle->oxorderarticles__oxvatprice->value,
            $oOrderArticle->oxorderarticles__oxvat->value,
            'pcs'
        );
    }

    /**
     * Returns item array by given price object
     *
     * @param  Price $oPrice
     * @param  string $sArtnum
     * @return array
     */
    protected function getSimpleItemArrayByPrice($oPrice, $sArtnum)
    {
        return $this->getItemArray(
            $sArtnum,
            $sArtnum,
            1,
            $oPrice->getNettoPrice(),
            $oPrice->getBruttoPrice(),
            $oPrice->getBruttoPrice(),
            $oPrice->getNettoPrice(),
            $oPrice->getVatValue(),
            $oPrice->getVat()
        );
    }

    /**
     * Returns an array with all order items
     *
     * @param  Order $oOrder
     * @param  bool  $blWithDiscounts
     * @return array
     */
    public function getOrderItems($oOrder, $blWithDiscounts = true)
    {
        $aItems = [];

        $aOrderArticleList = $oOrder->getOrderArticles();
        foreach ($aOrderArticleList->getArray() as $oOrderarticle) {
            $aItems[] = $this->getItemArrayByOrderArticle($oOrderarticle);
        }

        if ($oOrder->oxorder__oxdelcost->value != 0) {
            $aItems[] = $this->getSimpleItemArrayByPrice($oOrder->getOrderDeliveryPrice(), "shipping");
        }

        if ($oOrder->oxorder__oxpaycost->value != 0) {
            $aItems[] = $this->getSimpleItemArrayByPrice($oOrder->getOrderPaymentPrice(), "payment costs");
        }

        if ($oOrder->oxorder__oxwrapcost->value != 0) {
            $aItems[] = $this->getSimpleItemArrayByPrice($oOrder->getOrderWrappingPrice(), "Gift Wrapping");
        }

        if ($oOrder->oxorder__oxgiftcardcost->value != 0) {
            $aItems[] = $this->getSimpleItemArrayByPrice($oOrder->getOrderGiftCardPrice(), "Greeting Card");
        }

        if ($blWithDiscounts === true) {
            if ($oOrder->oxorder__oxvoucherdiscount->value != 0) {
                $oPrice = oxNew(Price::class);
                $oPrice->setBruttoPriceMode();
                $oPrice->setPrice($oOrder->oxorder__oxvoucherdiscount->value * -1, $oOrder->oxorder__oxartvat1->value);
                $aItems[] = $this->getSimpleItemArrayByPrice($oPrice, "voucher");
            }

            if ($oOrder->oxorder__oxdiscount->value != 0) {
                $oPrice = oxNew(Price::class);
                $oPrice->setBruttoPriceMode();
                $oPrice->setPrice($oOrder->oxorder__oxdiscount->value * -1, $oOrder->oxorder__oxartvat1->value);
                $aItems[] = $this->getSimpleItemArrayByPrice($oPrice, "discount");
            }
        }

        return $aItems;
    }

    /**
     * @param  array $aOrderItems
     * @param  string $sCaptureReference
     * @param  int $iQuantity
     * @return array
     */
    protected function getPartialOrderItems($aOrderItems, $sCaptureReference, $iQuantity)
    {
        $aPartialOrderItems = [];
        foreach ($aOrderItems as $aOrderItem) {
            if ($aOrderItem['reference'] == $sCaptureReference) {
                $aPartialOrderItems[] = $this->recalculateOrderItemForNewQuantity($aOrderItem, $iQuantity);
            }
        }
        return $aPartialOrderItems;
    }

    /**
     * Sums up the gross total amount of all items in the orderItems array
     *
     * @param  array $aOrderItems
     * @return int
     */
    public function getTotalAmountFromOrderItems($aOrderItems)
    {
        $dTotalAmount = 0;
        foreach ($aOrderItems as $aOrderItem) {
            $dTotalAmount += $aOrderItem['grossTotalAmount'];
        }
        return $dTotalAmount;
    }
}