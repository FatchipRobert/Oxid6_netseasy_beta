<?php

namespace Es\NetsEasy\Api;

use Es\NetsEasy\Core\DebugHandler;

class ReportingApi extends ReportingApi_parent
{

    /**
     * This function used for call API for fetching latest plug-in version
     * @return object
     */
    public function showPopup()
    {
        $stdClassObj = new \stdClass();
        $response = $this->callReportingApi();
        if ($response) {
            $stdClassObj->status = $response['status'];
            $stdClassObj->data = $response['data'];
        }
        return $stdClassObj;
    }

    /**
     * This function used for Custom API service to fetch plug-in latest version with notification.
     * @return array
     */
    public function callReportingApi()
    {
        $oDebugHandler = \oxNew(DebugHandler::class);
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Accept: application/json';
        $oxConfig = \oxNew(\OxidEsales\EshopCommunity\Core\Config::class);
        $aModuleVersions = $oxConfig->getConfigParam('aModuleVersions');
        $dataArray = array('merchant_id' => $oxConfig->getConfigParam('nets_merchant_id'),
            'plugin_name' => 'Oxid',
            'plugin_version' => '1.0.0', //$aModuleVersions['esnetseasy']
            'shop_url' => 'https://oxidlocal.sokoni.it/ee65/source/',
            'timestamp' => date('Y-m-d H:i:s')
        );
        $postData = json_encode($dataArray);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://ps17.sokoni.it/module/api/enquiry");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($postData) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }
        $oDebugHandler->log("API Request Data : " . $postData);
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        $responseData = '';
        $oDebugHandler->log("API Response HTTP Code : " . $info['http_code']);
        if (curl_error($ch)) {
            $oDebugHandler->log("API Response Error Data : " . json_encode(curl_error($ch)));
        }
        if ($info['http_code'] == 200) {
            if ($response) {
                $oDebugHandler->log("API Response Data : " . $response);
                $responseDecoded = json_decode($response);
                if ($responseDecoded->status == '00' || $responseDecoded->status == '11') {
                    $responseData = array('status' => $responseDecoded->status, 'data' => json_decode($responseDecoded->data));
                }
            }
        }
        return $responseData;
    }

}
