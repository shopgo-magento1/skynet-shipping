<?php

class Shopgo_SkynetShipping_Block_Adminhtml_System_Config_Form_ServiceListButton extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _construct()
    {
        parent::_construct();
        $template = $this->setTemplate('shopgo/skynet_shipping/system/config/service_list_button.phtml');
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    public function getAjaxActionUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/skynet/getservicelist');
    }

    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')->setData(
            array(
                'id'      => 'skynet_service_list_trigger',
                'label'   => $this->helper('adminhtml')->__('Retrieve Service List'),
                'onclick' => 'javascript:shopgo.skynetShipping.serviceListButton.getList(); return false;'
            )
        );

        return $button->toHtml();
    }
}
