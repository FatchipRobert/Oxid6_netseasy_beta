<?php

namespace Es\NetsEasy\Application\Helper;

use OxidEsales\Eshop\Core\Registry;

class Language
{
    /**
     * @var string[]
     */
    protected $netsLocaleMap = [
        "en" => "en-GB",
        "de" => "de-DE",
        "dk" => "da-DK",
        "se" => "sv-SE",
        "no" => "nb-NO",
        "fi" => "fi-FI",
        "pl" => "pl-PL",
        "nl" => "nl-NL",
        "fr" => "fr-FR",
        "es" => "es-ES",
    ];

    /**
     * @var Language
     */
    protected static $oInstance = null;

    /**
     * Create singleton instance of current helper class
     *
     * @return Language
     */
    public static function getInstance()
    {
        if (self::$oInstance === null) {
            self::$oInstance = oxNew(self::class);
        }
        return self::$oInstance;
    }

    /**
     * Returns locale code for javascript widget
     *
     * @return string
     */
    public function netsGetLocaleCode()
    {
        $sAbbreviation = Registry::getLang()->getLanguageAbbr();
        if (isset($this->netsLocaleMap[$sAbbreviation])) {
            return $this->netsLocaleMap[$sAbbreviation];
        }
        return "en-GB"; // fallback value
    }
}
