<?php

namespace Es\NetsEasy\extend\Application\Controller\Admin;

use Es\NetsEasy\Application\Helper\Api;
use Es\NetsEasy\Application\Helper\Curl;
use Es\NetsEasy\Application\Helper\DebugLog;
use OxidEsales\Eshop\Core\Registry;

class ModuleConfiguration extends ModuleConfiguration_parent
{
    /**
     * This function used for call API for fetching latest plug-in version
     *
     * @return object
     */
    public function netsGetModuleInfo()
    {
        $stdClassObj = new \stdClass();
        $response = $this->netsCallReportingApi();
        if ($response) {
            $stdClassObj->status = $response['status'];
            $stdClassObj->data = $response['data'];
        }
        return $stdClassObj;
    }

    /**
     * Return module version number
     *
     * @return string
     */
    protected function netsGetModuleVersion()
    {
        $module = oxNew(\OxidEsales\Eshop\Core\Module\Module::class);
        $module->load('esnetseasy');
        return $module->getInfo('version');
    }

    /**
     * This function used for Custom API service to fetch plug-in latest version with notification
     *
     * @return array|false
     */
    public function netsCallReportingApi()
    {
        $aParams = [
            'merchant_id' => Registry::getConfig()->getConfigParam('nets_merchant_id'),
            'plugin_name' => 'Oxid',
            'plugin_version' => $this->netsGetModuleVersion(),
            'shop_url' => 'https://oxidlocal.sokoni.it/ee65/source/',
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        $aHeaders = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $oCurl = Curl::getInstance();
        $sResponse = $oCurl->sendCurlRequest(Api::getInstance()->getReportingApiUrl(), 'POST', $aParams, $aHeaders);

        DebugLog::getInstance()->log("API Request Data : ".json_encode($aParams));
        DebugLog::getInstance()->log("API Response HTTP Code : ".$oCurl->getLastHttpCode());
        if ($oCurl->getLastError()) {
            DebugLog::getInstance()->log("API Response Error Data : " . json_encode($oCurl->getLastError()));
        }

        $aResponseData = false;
        if ($oCurl->getLastHttpCode() == 200) {
            if ($sResponse) {
                DebugLog::getInstance()->log("API Response Data : ".$sResponse);
                $oResponseDecoded = json_decode($sResponse);
                if ($oResponseDecoded->status == '00' || $oResponseDecoded->status == '11') {
                    $aResponseData = [
                        'status' => $oResponseDecoded->status,
                        'data' => json_decode($oResponseDecoded->data)
                    ];
                }
            }
        }
        return $aResponseData;
    }
}
