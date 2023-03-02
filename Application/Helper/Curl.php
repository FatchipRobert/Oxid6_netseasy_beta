<?php

namespace Es\NetsEasy\Application\Helper;

class Curl
{
    /**
     * @var int|null
     */
    protected $iHttpCode = null;

    /**
     * @var string|null
     */
    protected $sError = null;

    /**
     * @var Curl
     */
    protected static $oInstance = null;

    /**
     * Create singleton instance of current helper class
     *
     * @return Curl
     */
    public static function getInstance()
    {
        if (self::$oInstance === null) {
            self::$oInstance = oxNew(self::class);
        }
        return self::$oInstance;
    }

    /**
     * Returns http response code of the last curl request
     *
     * @return int|null
     */
    public function getLastHttpCode()
    {
        return $this->iHttpCode;
    }

    /**
     * Sets http response code of the last curl request
     *
     * @return void
     */
    public function setLastHttpCode($iHttpCode)
    {
        $this->iHttpCode = $iHttpCode;
    }

    /**
     * Returns error message of the last curl request
     *
     * @return int|null
     */
    public function getLastError()
    {
        return $this->sError;
    }

    /**
     * Sets error message of the last curl request
     *
     * @return void
     */
    public function setLastError($sError)
    {
        $this->sError = null; // reset do default first
        if (!empty($sError)) {
            $this->sError = $sError;
        }
    }

    /**
     * Sends a curl request. Returns the response
     *
     * @param  string $sUrl
     * @param  string $sMethod
     * @param  array|false $aParams
     * @return string
     */
    public function sendCurlRequest($sUrl, $sMethod = "POST", $aParams = false, $aHeaders = false)
    {
        $oCurl = curl_init();
        curl_setopt($oCurl, CURLOPT_URL, $sUrl);
        curl_setopt($oCurl, CURLOPT_CUSTOMREQUEST, $sMethod);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        if (in_array($sMethod, ['POST', 'PUT']) && !empty($aParams)) {
            curl_setopt($oCurl, CURLOPT_POSTFIELDS, json_encode($aParams));
        }
        if (!empty($aHeaders)) {
            curl_setopt($oCurl, CURLOPT_HTTPHEADER, $aHeaders);
        }
        $result = curl_exec($oCurl);
        $info = curl_getinfo($oCurl);
        $this->setLastHttpCode($info['http_code']);
        $this->setLastError(curl_error($oCurl));

        curl_close($oCurl);

        return $result;
    }
}
