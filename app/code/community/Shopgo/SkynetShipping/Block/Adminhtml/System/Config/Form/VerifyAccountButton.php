<?php

class Shopgo_SkynetShipping_Block_Adminhtml_System_Config_Form_VerifyAccountButton extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _construct()
    {
        parent::_construct();
        $template = $this->setTemplate('shopgo/skynet_shipping/system/config/verify_account_button.phtml');
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    public function getAjaxVerifyAccountUrl()
    {
        return Mage::helper('adminhtml')->getUrl('adminhtml/skynet/verifyaccount');
    }

    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')->setData(
            array(
                'id'      => 'skynet_account_checker',
                'label'   => $this->helper('adminhtml')->__('Verify Account'),
                'onclick' => 'javascript:shopgo.skynetShipping.verifyAccountButton.verifyAccount(); return false;'
            )
        );

        return $button->toHtml();
    }
}
