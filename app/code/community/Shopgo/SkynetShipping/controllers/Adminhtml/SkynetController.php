<?php

class Shopgo_SkynetShipping_Adminhtml_SkynetController extends Mage_Adminhtml_Controller_Action
{
    public function verifyAccountAction()
    {
        $params =  $this->getRequest()->getPost();
        $helper =  Mage::helper('skynetshipping');
        $response = Mage::app()->getResponse()
            ->setHeader('content-type', 'application/json; charset=utf-8');

        $result = array(
            'status' => 0,
            'description' => $this->__('An error has occurred. Please, contact the store administrator.')
        );

        $clientInfo = $helper->getOriginSupplier('skynet_account');

        foreach ($clientInfo as $key => $val) {
            if (empty($params[$key]) && $params[$key] !== 0) {
                $result['description'] = $this->__(
                    'Username, Password, Station Code and Consignor Account are required in order to verify your account'
                );
                $response->setBody(json_encode($result));

                return;
            }
        }

        if (!$params['username_changed'] && $params['username'] == '******') {
            $params['username'] = $helper->getConfigData('username', 'carriers_skynet');
            $params['username'] = Mage::helper('core')->decrypt($params['username']);
        }

        if (!$params['password_changed'] && $params['password'] == '******') {
            $params['password'] = $helper->getConfigData('password', 'carriers_skynet');
            $params['password'] = Mage::helper('core')->decrypt($params['password']);
        }

        $result = Mage::getModel('skynetshipping/shipment')->verifyAccount($params);

        $response->setBody(json_encode($result));
    }

    public function getServiceListAction()
    {
        $params =  $this->getRequest()->getPost();
        $helper =  Mage::helper('skynetshipping');
        $response = Mage::app()->getResponse()
            ->setHeader('content-type', 'application/json; charset=utf-8');

        $result = array(
            'status' => 0,
            'description' => $this->__('An error has occurred. Please, contact the store administrator.')
        );

        $validValues = array(
            'username_changed' => array(0, 1),
            'password_changed' => array(0, 1)
        );

        if (!isset($params['username_changed']) || !isset($params['password_changed'])) {
            $result['description'] = $this->__('Bad request');
            $response->setBody(json_encode($result));

            return;
        } elseif (!in_array($params['username_changed'], $validValues['username_changed'])
                  || !in_array($params['password_changed'], $validValues['password_changed'])) {
            $result['description'] = $this->__('Bad request');
            $response->setBody(json_encode($result));

            return;
        }

        foreach ($params as $p) {
            if (empty($p) && $p != 0) {
                $result['description'] = $this->__(
                    'Username, Password, Station Code and Consignee Number are necessary in order to get services list'
                );
                $response->setBody(json_encode($result));

                return;
            }
        }

        if (!$params['username_changed'] && $params['username'] == '******') {
            $params['username'] = $helper->getConfigData('username', 'carriers_skynet');
            $params['username'] = Mage::helper('core')->decrypt($params['username']);
        }

        if (!$params['password_changed'] && $params['password'] == '******') {
            $params['password'] = $helper->getConfigData('password', 'carriers_skynet');
            $params['password'] = Mage::helper('core')->decrypt($params['password']);
        }

        $result = Mage::getModel('skynetshipping/service')->getList($params);

        $response->setBody(json_encode($result));
    }
}
