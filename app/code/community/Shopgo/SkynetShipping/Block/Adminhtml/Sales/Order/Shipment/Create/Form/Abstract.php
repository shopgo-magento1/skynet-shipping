<?php

abstract class Shopgo_SkynetShipping_Block_Adminhtml_Sales_Order_Shipment_Create_Form_Abstract extends Mage_Adminhtml_Block_Sales_Order_Abstract
{
    abstract protected function isEnabled();

    abstract protected function getFormFieldData($field, $data = '');

    /**
     * Retrieve invoice order
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->getShipment()->getOrder();
    }

    /**
     * Retrieve shipment model instance
     *
     * @return Mage_Sales_Model_Order_Shipment
     */
    public function getShipment()
    {
        return Mage::registry('current_shipment');
    }

    public function getCountryCodeOptions()
    {
        return Mage::getModel('adminhtml/system_config_source_country')->toOptionArray();
    }
}
