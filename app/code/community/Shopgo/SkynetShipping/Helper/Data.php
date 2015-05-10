<?php

class Shopgo_SkynetShipping_Helper_Data extends Shopgo_ShippingCore_Helper_Abstract
{
    const GENERAL_CONTACT_EMAIL              = 'trans_email/ident_general/email';

    const CARRIERS_SKYNET_SYSTEM_PATH        = 'carriers/skynet/';
    const SHIPPING_ORIGIN_SYSTEM_PATH        = 'shipping/origin/';
    const SKYNET_SETTINGS_SYSTEM_PATH        = 'shipping/skynet_settings/';

    const RATE_RESPONSE_CURRENCY_XPATH       = 'TARIF/RATECURRENCY';
    const RATE_RESPONSE_FINAL_CHARGES_XPATH  = 'TARIF/FINALCHARGES';
    const RATE_RESPONSE_VOL_WEIGHT_XPATH     = 'TARIF/VOLWEIGHTOFSHIPMENT';
    const RATE_RESPONSE_CHARGED_WEIGHT_XPATH = 'TARIF/CHARGEDWEIGHT';

    const SHIPMENT_RESPONSE_NUMBER_XPATH     = 'ShipmentNumber';
    //const PICKUP_RESPONSE_REF_NUMBER_XPATH   = 'PickupReferenceNumber';
    //const TRACKING_RESPONSE_HISTORY_XPATH    = 'TODO';
    //const LABEL_RESPONSE_PDF_XPATH           = 'TODO';
    const SERVICE_LIST_RESPONSE_XPATH        = 'ServiceList';


    protected $_logFile = 'skynet_shipping.log';


    public function getOriginSupplier($section = '')
    {
        $data = array();

        $originInfo = array(
            'consignor_name'         => ucwords(strtolower(trim($this->getConfigData('consignor_name', 'skynet_settings')))),
            'country_code'           => strtoupper(trim($this->getConfigData('country_id', 'shipping_origin'))),
            'state_or_province_code' => trim($this->getConfigData('region_id', 'shipping_origin')),
            'zipcode'                => trim($this->getConfigData('postcode', 'shipping_origin')),
            'city'                   => trim($this->getConfigData('city', 'shipping_origin')),
            'address_line1'          => trim($this->getConfigData('street_line1', 'shipping_origin')),
            'address_line2'          => trim($this->getConfigData('street_line2', 'shipping_origin')),
            'phone_number'           => trim($this->getConfigData('phone_number', 'additional_info')),
            'fax_number'             => trim($this->getConfigData('fax_number', 'additional_info')),
            'cellphone'              => trim($this->getConfigData('cellphone', 'additional_info')),
            'email'                  => trim($this->getConfigData('email', 'additional_info'))
        );

        $skynetAccount = array(
            'username'          => trim(Mage::helper('core')->decrypt($this->getConfigData('username', 'carriers_skynet'))),
            'password'          => trim(Mage::helper('core')->decrypt($this->getConfigData('password', 'carriers_skynet'))),
            'station_code'      => strtoupper(trim($this->getConfigData('station_code', 'carriers_skynet'))),
            'consignor_account' => trim($this->getConfigData('consignor_account', 'carriers_skynet'))
        );

        switch ($section) {
            case 'origin_info':
                $data = $originInfo;
                break;
            case 'skynet_account':
                $data = $skynetAccount;
                break;
            default:
                $data = array_merge($skynetAccount, $originInfo);
        }

        return $data;
    }

    public function getClientInfo($source)
    {
        if (!$source) {
            $source = $this->getOriginSupplier('skynet_account');
        }

        $clientInfo = array(
            'UserName'         => trim($source['username']),
            'Password'         => trim($source['password']),
            'StationCode'      => strtoupper(trim($source['station_code'])),
            'ConsignorAccount' => trim($source['consignor_account'])
        );

        return $clientInfo;
    }

    public function getConfigData($var, $type, $store = null)
    {
        $path = '';

        switch ($type) {
            case 'carriers_skynet':
                $path = self::CARRIERS_SKYNET_SYSTEM_PATH;
                break;
            case 'shipping_origin':
                $path = self::SHIPPING_ORIGIN_SYSTEM_PATH;
                break;
            case 'skynet_settings':
                $path = self::SKYNET_SETTINGS_SYSTEM_PATH;
                break;
        }

        return Mage::getStoreConfig($path . $var, $store);
    }

    public function soapClient($method, $callParams, $scOptions = array())
    {
        $wsdl = $this->_getWsdl($method);
        $result = null;

        if (!isset($scOptions['soap_version'])) {
            $scOptions['soap_version'] = SOAP_1_2;
        }

        if (!isset($scOptions['trace'])) {
            $scOptions['trace'] = 1;
        }

        if (!isset($scOptions['exceptions'])) {
            $scOptions['exceptions'] = 0;
        }

        try {
            $soapClient = new SoapClient($wsdl, $scOptions);

            if ($actionHeader = $this->_getSoapHeader($method)) {
                $soapClient->__setSoapHeaders($actionHeader);
            }

            $result = $this->_soapClientCall($method, $soapClient, $callParams);

            if ($result instanceof SoapFault) {
                $this->log($result->faultstring);
                $result = '[soapfault]';
            }
        } catch (SoapFault $sf) {
            $this->log($sf->faultstring);
            $result = '[soapfault]';
        }

        return $result;
    }

    private function _getSoapHeader($method)
    {
        $action = '';
        $header = null;

        switch ($method) {
            case 'rate_request':
                $action = 'http://tempuri.org/IService1/RequestRatesByObject';
                break;
            case 'create_shipment':
                $action = 'http://tempuri.org/IService1/CreateShipmentByObject';
                break;
            case 'pickup_request':
                $action = 'http://tempuri.org/IService1/PickupRequestByObject';
                break;
            case 'get_tracking':
                $action = 'http://tempuri.org/GetSkyBillTrack';
                break;
            case 'get_label_pdf':
                $action = 'http://tempuri.org/IService1/GetLabelPDF';
                break;
            case 'verify_account':
                $action = 'http://tempuri.org/IService1/VerifyUserAccount';
                break;
            case 'get_service_list':
                $action = 'http://tempuri.org/IService1/GetServiceList';
                break;
            default:
                return $header;
        }

        $header = new SoapHeader(
            'http://www.w3.org/2005/08/addressing',
            'Action',
            $action,
            true
        );

        return $header;
    }

    private function _soapClientCall($method, $soapClient, $callParams)
    {
        $result = null;

        switch ($method) {
            case 'rate_request':
                $result = $soapClient->RequestRatesByObject($callParams)->RequestRatesByObjectResult;
                break;
            case 'create_shipment':
                $result = $soapClient->CreateShipmentByObject($callParams)->CreateShipmentByObjectResult;
                break;
            case 'pickup_request':
                $result = $soapClient->PickupRequestByObject($callParams)->PickupRequestByObjectResult;
                break;
            case 'get_tracking':
                $result = $soapClient->GetSkyBillTrack($callParams)->GetSkyBillTrackResult;
                break;
            case 'get_label_pdf':
                $result = $soapClient->GetLabelPDF($callParams)->GetLabelPDFResult;
                break;
            case 'verify_account':
                $result = $soapClient->VerifyUserAccount($callParams)->VerifyUserAccountResult;
                break;
            case 'get_service_list':
                $result = $soapClient->GetServiceList($callParams)->GetServiceListResult;
                break;
        }

        return $result;
    }

    private function _getWsdl($name)
    {
        $wsdl = 'http://api.skynetwwe.info/Service1.svc?wsdl';

        switch ($name) {
            case 'get_tracking':
                $wsdl = 'https://iskynettrack.skynetwwe.info/TrackingService/TrackingService_v1.asmx?wsdl';
                break;
        }

        return $wsdl;
    }

    public function debug($params, $file = '')
    {
        if ($this->getConfigData('debug', 'carriers_skynet')) {
            $this->log($params, '', $file);
        }
    }

    public function hideLogPrivacies($data)
    {
        $mask = '******';

        $data['UserName'] = $mask;
        $data['Password'] = $mask;

        return $data;
    }

    public function parseXmlResponse($xml, $method)
    {
        $result = array();

        $xmlObj = new Varien_Simplexml_Config($xml);

        if (empty($xmlObj)) {
            return $result;
        }

        $result['status'] = $xmlObj->getNode('StatusCode')->asArray();
        $result['status_description'] = $xmlObj->getNode('StatusDescription')->asArray();
        $result['request_id'] = $xmlObj->getNode('RequestID')->asArray();

        if (false === strpos($result['status'], 'ERR')) {
            switch ($method) {
                case 'rate_request':
                    $result['currency']       = $xmlObj->getNode(self::RATE_RESPONSE_CURRENCY_XPATH)->asArray();
                    $result['price']          = $xmlObj->getNode(self::RATE_RESPONSE_FINAL_CHARGES_XPATH)->asArray();
                    $result['vol_weight']     = $xmlObj->getNode(self::RATE_RESPONSE_VOL_WEIGHT_XPATH)->asArray();
                    $result['charged_weight'] = $xmlObj->getNode(self::RATE_RESPONSE_CHARGED_WEIGHT_XPATH)->asArray();
                    break;
                case 'shipping_service':
                    $result['shipment_number'] = $xmlObj->getNode(self::SHIPMENT_RESPONSE_NUMBER_XPATH)->asArray();
                    break;
                //case 'pickup_request':
                    //$result['ref_number'] = $xmlObj->getNode(self::PICKUP_RESPONSE_REF_NUMBER_XPATH)->asArray();
                    //break;
                //case 'get_tracking':
                    //$result['history'] = $xmlObj->getNode(self::TRACKING_RESPONSE_HISTORY_XPATH)->asArray();
                    //break;
                //case 'get_label_pdf':
                    //$result['label'] = $xmlObj->getNode(self::LABEL_RESPONSE_PDF_XPATH)->asArray();
                    //break;
                case 'verify_account':
                    $result['verify_account'] = $xmlObj->getNode()->asArray();
                    break;
                case 'get_service_list':
                    $result['service_list'] = (array) $xmlObj->getNode(self::SERVICE_LIST_RESPONSE_XPATH);
                    $result['service_list'] = $result['service_list']['string'];
                    break;
            }
        }

        return $result;
    }

    public function _getAdminhtmlShipmentForms($block)
    {
        $html = $block->getChildHtml('skynet_shipment');

        return $html;
    }
}
