<?php

namespace Es\NetsEasy\ShopExtend\Application\Models;

use Es\NetsEasy\Core\CommonHelper;

/**
 * Class defines preparation of Nets order items
 */
class OrderItems
{

    protected $oCommonHelper;
    protected $oOrderItems;

    public function __construct($oOrderItems = null, $commonHelper = null)
    {

        if (!$oOrderItems) {
            $this->oOrderItems = $this;
        } else {
            $this->oOrderItems = $oOrderItems;
        }
        // works only if StaticHelper is not autoloaded yet!
        if (!$commonHelper) {
            $this->oCommonHelper = \oxNew(CommonHelper::class);
        } else {
            $this->oCommonHelper = $commonHelper;
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
        $items = [];
        foreach ($oArticles as $listitem) {
            $items[] = $this->oOrderItems->getItemList($listitem);
            $totalOrderAmt += $this->oOrderItems->prepareAmount($listitem->oxorderarticles__oxbrutprice->rawValue);
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
                $items[] = $this->oOrderItems->getPayCost($item);
                $totalOrderAmt += $this->oOrderItems->prepareAmount($item->oxorder__oxpaycost->rawValue);
            }
            // greeting card if sent as item
            if ($item->oxorder__oxgiftcardcost->rawValue > 0) {
                $items[] = $this->oOrderItems->getGreetingCardItem($item);
                $totalOrderAmt += $this->oOrderItems->prepareAmount($item->oxorder__oxgiftcardcost->rawValue);
            }
            // gift wrapping if sent as item
            if ($item->oxorder__oxwrapcost->rawValue > 0) {
                $items[] = $this->oOrderItems->getGiftWrappingItem($item);
                $totalOrderAmt += $this->oOrderItems->prepareAmount($item->oxorder__oxwrapcost->rawValue);
            }
            // shipping cost if sent as item
            if ($item->oxorder__oxdelcost->rawValue > 0) {
                $items[] = $this->oOrderItems->getShippingCost($item);
                $totalOrderAmt += $this->prepareAmount($item->oxorder__oxdelcost->rawValue);
            }
        }
        return [
            "items" => $items,
            "totalAmt" => $totalOrderAmt
        ];
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
     * Function to prepare amount
     * @return int
     */

    public function prepareAmount($amount = 0)
    {
        return (int) round($amount * 100);
    }

}
