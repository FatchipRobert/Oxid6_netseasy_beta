<?php

namespace Es\NetsEasy\ShopExtend\Application\Models;

/**
 * Nets basket class
 * @mixin Es\NetsEasy\ShopExtend\Application\Model\Order
 */
class BasketItems
{

    protected $oOrder;

    /**
     * Constructor
     * @param  object $oOrder The Order model injected object
     * @return Null
     */
    public function __construct($oOrder = null)
    {
        if (!$oOrder) {
            $this->oOrder = $this;
        } else {
            $this->oOrder = $oOrder;
        }
    }

    /**
     * Function to get item list array
     * @param  object $oBasket The basket model object
     * @return null
     */
    public function getItemList($oBasket)
    {
        $wrapCost = $greetCardAmt = $shippingCost = $payCost = 0;
        $shippingCost = $oBasket->getDeliveryCost();
        if ($shippingCost) {
            $shipCostAmt = $oBasket->isCalculationModeNetto() ? $shippingCost->getNettoPrice() : $shippingCost->getBruttoPrice();
        }
        if ($shipCostAmt > 0) {
            $shipCostAmt = round(round($shipCostAmt, 2) * 100);
            $items[] = [
                'reference' => 'shipping',
                'name' => 'shipping',
                'quantity' => 1,
                'unit' => 'units',
                'unitPrice' => $shipCostAmt,
                'taxAmount' => 0,
                'grossTotalAmount' => $shipCostAmt,
                'netTotalAmount' => $shipCostAmt
            ];
        }
        $payCost = $oBasket->getPaymentCost();
        if ($payCost) {
            $payCostAmt = $oBasket->isCalculationModeNetto() ? $payCost->getNettoPrice() : $payCost->getBruttoPrice();
        }
        if ($payCostAmt > 0) {
            $payCostAmt = round(round($payCostAmt, 2) * 100);
            $items[] = [
                'reference' => 'payment costs',
                'name' => 'payment costs',
                'quantity' => 1,
                'unit' => 'units',
                'unitPrice' => $payCostAmt,
                'taxAmount' => 0,
                'grossTotalAmount' => $payCostAmt,
                'netTotalAmount' => $payCostAmt
            ];
        }
        $discAmount = $this->oOrder->getDiscountSum($oBasket);
        if ($discAmount > 0) {
            $items[] = [
                'reference' => 'discount',
                'name' => 'discount',
                'quantity' => 1,
                'unit' => 'units',
                'unitPrice' => - $discAmount,
                'taxAmount' => 0,
                'grossTotalAmount' => - $discAmount,
                'netTotalAmount' => - $discAmount
            ];
        }
        return $items;
    }

    /**
     * Function to get all type of discounts altogether and pass it to nets api
     * @param  object $basket The basket model object
     * @return float
     */
    public function getDiscountSum($basket)
    {
        $discount = 0.0;
        $totalDiscount = $basket->getTotalDiscount();
        if ($totalDiscount) {
            $discount += $totalDiscount->getBruttoPrice();
        }
        // if payment costs are negative, adding them to discount
        if (($costs = $basket->getPaymentCosts()) < 0) {
            $discount += ($costs * - 1);
        }
        // vouchers, coupons
        $vouchers = (array) $basket->getVouchers();
        foreach ($vouchers as $voucher) {
            $discount += round($voucher->dVoucherdiscount, 2);
        }
        // final discount amount
        return round(round($discount, 2) * 100);
    }

    /**
     * Function to get discount item array
     * @param  int $wrapCost The wrap cost number
     * @param  array $greetCardAmt The greet cart amount
     * @return null
     */
    public function getDiscountItem($wrapCost, $greetCardAmt)
    {
        if ($wrapCost > 0) {
            $items[] = [
                'reference' => 'Gift Wrapping',
                'name' => 'Gift Wrapping',
                'quantity' => 1,
                'unit' => 'units',
                'unitPrice' => $wrapCost,
                'taxAmount' => 0,
                'grossTotalAmount' => $wrapCost,
                'netTotalAmount' => $wrapCost
            ];
        }
        if ($greetCardAmt > 0) {
            $items[] = [
                'reference' => 'Greeting Card',
                'name' => 'Greeting Card',
                'quantity' => 1,
                'unit' => 'units',
                'unitPrice' => $greetCardAmt,
                'taxAmount' => 0,
                'grossTotalAmount' => $greetCardAmt,
                'netTotalAmount' => $greetCardAmt
            ];
        }
        return $items;
    }

    /**
     * Function to get product item
     * @param  array $item The items array
     * @return array
     */
    public function getProductItem($item)
    {
        $quantity = $item->getAmount();
        $prodPrice = $item->getArticle()
                ->getPrice(1)
                ->getBruttoPrice(); // product price incl. VAT in DB format
        $tax = $item->getPrice()->getVat(); // Tax rate in DB format
        $taxFormat = '1' . str_pad(number_format((float) $tax, 2, '.', ''), 5, '0', STR_PAD_LEFT);
        $unitPrice = round(round(($prodPrice * 100) / $taxFormat, 2) * 100);
        $netAmount = round($quantity * $unitPrice);
        $grossAmount = round($quantity * ($prodPrice * 100));
        return [
            'reference' => $item->getArticle()->oxarticles__oxartnum->value,
            'name' => $item->getArticle()->oxarticles__oxtitle->value,
            'quantity' => $quantity,
            'unit' => 'pcs',
            'unitPrice' => $unitPrice,
            'taxRate' => $item->getPrice()->getVat() * 100,
            'taxAmount' => $grossAmount - $netAmount,
            'grossTotalAmount' => $grossAmount,
            'netTotalAmount' => $netAmount
        ];
    }

}
