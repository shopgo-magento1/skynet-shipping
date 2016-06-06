<?php

class Shopgo_SkynetShipping_Model_Service extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('skynetshipping/service');
    }

    public function getList($params)
    {
        $helper = Mage::helper('skynetshipping');

        $data = array(
            'UserName' => $params['username'],
            'Password' => $params['password'],
            'StationCode' => $params['station_code'],
            'ConsignorAccount' => $params['consignor_account']
        );

        $result = array(
            'status' => 0,
            'description' => $helper->__('An error has occurred. Please, contact the store administrator.')
        );

        $soapResult = $helper->soapClient('get_service_list', $data);

        $debugLog = array(
            array('message' => $helper->hideLogPrivacies($data)),
            array('message' => $soapResult)
        );

        $helper->debug($debugLog, 'skynet_service_list');

        if ($soapResult != '[soapfault]') {
            $response = $helper->parseXmlResponse($soapResult, 'get_service_list');

            if (false === strpos($response['status'], 'ERR')) {
                $this->_saveServiceData($response['service_list']);

                $list = Mage::getModel('skynetshipping/system_config_source_service')
                    ->toOptionArray();

                $result = array(
                    'status' => 1,
                    'description' => $helper->__('Service list has been retrieved successfully!'),
                    'list' => $list
                );
            } else {
                $result['description'] = $response['status_description'];
            }
        }

        return $result;
    }

    public function getServiceText($value)
    {
        $text       = '';
        $collection = Mage::getModel('skynetshipping/service')->getCollection();

        foreach ($collection as $item) {
            if ($value == $item->getServiceId()) {
                $text = $item->getService();
                break;
            }
        }

        return $text;
    }

    private function _saveServiceData($data)
    {
        Mage::getResourceModel('skynetshipping/service')
            ->truncate();

        foreach ($data as $i) {
            Mage::getModel('skynetshipping/service')
                ->setService($i)
                ->save();
        }
    }
}
