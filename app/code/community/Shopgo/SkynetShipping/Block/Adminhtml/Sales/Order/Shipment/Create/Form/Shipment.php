<?php

class Shopgo_SkynetShipping_Block_Adminhtml_Sales_Order_Shipment_Create_Form_Shipment extends Shopgo_SkynetShipping_Block_Adminhtml_Sales_Order_Shipment_Create_Form_Abstract
{
    protected function _construct()
    {
        parent::_construct();

        $shipmentData = Mage::getSingleton('adminhtml/session')->getShipSkynetShipmentData();

        if ($shipmentData) {
            $this->setFormData($shipmentData);

            Mage::getSingleton('adminhtml/session')->unsShipSkynetShipmentData();

            if (Mage::registry('setShipSkynetFormDefaultData')) {
                Mage::unregister('setShipSkynetFormDefaultData');
            }
        } else {
            if (!Mage::registry('setShipSkynetFormDefaultData')) {
                Mage::register('setShipSkynetFormDefaultData', 1);
            }
        }
    }

    protected function isEnabled()
    {
        return Mage::getModel('skynetshipping/shipment')->isEnabled();
    }

    public function getShipmentTypeOptions()
    {
        $options = Mage::getModel('skynetshipping/shipment_source_typeofshipment')
            ->toOptionArray();

        return $options;
    }

    protected function getFormFieldData($field, $data = '')
    {
        if (Mage::registry('setShipSkynetFormDefaultData')) {
            switch ($field) {
                case 'consignor_ref':
                    $data = $this->getOrder()->getIncrementId();
                    break;
            }
        }

        return $data;
    }
}
