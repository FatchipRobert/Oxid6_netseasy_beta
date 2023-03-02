<?php

namespace Es\NetsEasy\Application\Model\Api;

use Es\NetsEasy\Application\Helper\Api;
use Es\NetsEasy\Application\Helper\Curl;
use Es\NetsEasy\Application\Helper\DebugLog;

abstract class BaseRequest
{
    const METHOD_GET = "GET";
    const METHOD_POST = "POST";
    const METHOD_PUT = "PUT";

    /**
     * @var string|null
     */
    protected $sEndpoint = null;

    /**
     * @var string|null
     */
    protected $sRequestMethod = null;

    /**
     * @var array|null
     */
    protected $aUrlParameters = null;

    /**
     * @var array|null
     */
    protected $aRequestParameters = null;

    /**
     * Function to fetch secret key to pass as authorization
     *
     * @return array
     */
    public function getHeaders()
    {
        return [
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization: ".Api::getInstance()->getSecretKey(),
            "CommercePlatformTag: Oxid ".Api::getInstance()->getShopIdentifier(),
        ];
    }

    /**
     * Returns used request method
     *
     * @return string|null
     */
    public function getRequestMethod()
    {
        return $this->sRequestMethod;
    }

    /**
     * Returns api endpoint
     *
     * @return string|null
     */
    protected function getEndpoint()
    {
        return $this->sEndpoint;
    }

    /**
     * Sets url parameters array
     *
     * @param  array $aRequestParameters
     * @return void
     */
    protected function setUrlParameters($aRequestParameters)
    {
        $this->aUrlParameters = $aRequestParameters;
    }

    /**
     * Returns url parameters
     *
     * @return array|null
     */
    protected function getUrlParameters()
    {
        return $this->aUrlParameters;
    }

    /**
     * Returns parameters that were used for the API request
     *
     * @return array|null
     */
    public function getRequestParameters()
    {
        return $this->aRequestParameters;
    }

    /**
     * Sets requestParameters property
     *
     * @param  array $aParams
     * @return void
     */
    protected function setRequestParameters($aParams)
    {
        $this->aRequestParameters = $aParams;
    }

    /**
     * Generates the request URL by joining the API URL of the configured live/test mode with the endpoint of the desired call
     * Also handles url parameter replacement
     *
     * @return string
     */
    protected function getRequestUrl()
    {
        $sEndpoint = $this->getEndpoint();

        $aRequestParameters = $this->getUrlParameters();
        if (!empty($aRequestParameters)) {
            foreach ($aRequestParameters as $sKey => $sValue) {
                $sEndpoint = str_ireplace($sKey, $sValue, $sEndpoint);
            }
        }
        return rtrim(Api::getInstance()->getApiUrl(), "/").$sEndpoint;
    }

    /**
     * Function to curl request to execute api calls
     *
     * @param  array|false $aParams
     * @return string
     */
    protected function sendCurlRequest($aParams = false)
    {
        if ($this->sEndpoint === null) {
            throw new \Exception("NetsRequest: Entpoint is not set!");
        }

        if ($this->sRequestMethod === null) {
            throw new \Exception("NetsRequest: Request method is not set!");
        }

        $sRequestUrl = $this->getRequestUrl();

        DebugLog::getInstance()->log("NetsApi ".(new \ReflectionClass($this))->getShortName().": Send request to url ".$sRequestUrl." - params: ".json_encode($aParams, true));

        // add params to object to make them accessible later on for logging and similar things
        $this->setRequestParameters($aParams);

        $oCurl = Curl::getInstance();
        $sResponse = $oCurl->sendCurlRequest($sRequestUrl, $this->getRequestMethod(), $aParams, $this->getHeaders());

        switch ($oCurl->getLastHttpCode()) {
            case 401:
                $error_message = 'NETS Easy authorization filed. Check your secret/checkout keys';
                break;
            case 400:
                $error_message = 'NETS Easy Bad request: Please check request params/headers ';
                break;
            case 500:
                $error_message = 'Unexpected error';
                break;
        }
        if (!empty($error_message)) {
            DebugLog::getInstance()->log("netsOrder Curl request error, ".$error_message);
        }

        DebugLog::getInstance()->log("NetsApi ".(new \ReflectionClass($this))->getShortName().": Response: ".$sResponse);

        $aResponse = json_decode($sResponse, true);
        return $aResponse;
    }
}
