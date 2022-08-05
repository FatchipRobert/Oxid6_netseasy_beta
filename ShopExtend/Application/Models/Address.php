<?php

namespace Es\NetsEasy\ShopExtend\Application\Models;

/**
 * Nets address class
 * @mixin Es\NetsEasy\ShopExtend\Application\Model\Address
 */
class Address
{

    /**
     * Function to get dDelivery address array
     * @return array
     */
    public function getDeliveryAddress($oOrder, $oDB, $oUser)
    {
        $oDelAd = $oOrder->getDelAddressInfo();
        if ($oDelAd) {
            $delivery_address = new \stdClass();
            $delivery_address->firstname = $oDelAd->oxaddress__oxfname->value;
            $delivery_address->lastname = $oDelAd->oxaddress__oxlname->value;
            $delivery_address->street = $oDelAd->oxaddress__oxstreet->value;
            $delivery_address->housenumber = $oDelAd->oxaddress__oxstreetnr->value;
            $delivery_address->zip = $oDelAd->oxaddress__oxzip->value;
            $delivery_address->city = $oDelAd->oxaddress__oxcity->value;
            $sDelCountry = $oDelAd->oxaddress__oxcountryid->value;
            $delivery_address->country = $oDB->getOne("SELECT oxisoalpha3 FROM oxcountry WHERE oxid = ?", [
                $sDelCountry
            ]);
            $delivery_address->company = $oDelAd->oxaddress__oxcompany->value;
            return $delivery_address;
        } else {
            $delivery_address = new \stdClass();
            $delivery_address->firstname = $oUser->oxuser__oxfname->value;
            $delivery_address->lastname = $oUser->oxuser__oxlname->value;
            $delivery_address->street = $oUser->oxuser__oxstreet->value;
            $delivery_address->housenumber = $oUser->oxuser__oxstreetnr->value;
            $delivery_address->zip = $oUser->oxuser__oxzip->value;
            $delivery_address->city = $oUser->oxuser__oxcity->value;
            $delivery_address->country = $oDB->getOne("SELECT oxisoalpha3 FROM oxcountry WHERE oxid = ?", [
                $oUser->oxuser__oxcountryid->value
            ]);
            $delivery_address->company = $oUser->oxuser__oxcompany->value;
            return $delivery_address;
        }
    }

    /**
     * Function to set address
     * @return array
     */
    public function setAddress($oUser, $sTranslation, $oBasket)
    {
        $oLang = \oxRegistry::getLang();
        $iLang = 0;
        $iLang = $oLang->getTplLanguage();
        if (!isset($iLang)) {
            $iLang = $oLang->getBaseLanguage();
        }
        try {
            $sTranslation = $oLang->translateString($oUser->oxuser__oxsal->value, $iLang, isAdmin());
        } catch (oxLanguageException $oEx) {
            // is thrown in debug mode and has to be caught here, as smarty hangs otherwise!
        }
        $daten['checkout_type'] = \oxRegistry::getConfig()->getConfigParam('nets_checkout_mode');
        $lang_abbr = $oLang->getLanguageAbbr($iLang);
        if (isset($lang_abbr) && $lang_abbr === 'en') {
            $daten['language'] = 'en_US';
        } else if (isset($lang_abbr) && $lang_abbr === 'de') {
            $daten['language'] = 'de_DE';
        }
        $daten['title'] = $sTranslation;
        $daten['name_affix'] = $oUser->oxuser__oxaddinfo->value;
        $daten['telephone'] = $oUser->oxuser__oxfon->value;
        $daten['dob'] = $oUser->oxuser__oxbirthdate->value;
        $daten['email'] = $oUser->oxuser__oxusername->value;
        $daten['amount'] = intval(strval($oBasket->getPrice()->getBruttoPrice() * 100));
        $daten['currency'] = $oBasket->getBasketCurrency()->name;
        return $daten;
    }

}
