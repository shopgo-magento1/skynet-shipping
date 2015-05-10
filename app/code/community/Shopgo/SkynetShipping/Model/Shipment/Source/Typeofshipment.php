<?php

class Shopgo_SkynetShipping_Model_Shipment_Source_Typeofshipment
{
    public function toOptionArray($pleaseSelect = false)
    {
        $helper = Mage::helper('skynetshipping');

        $options = array(
            array('label' => $helper->__('NON DOCS'),
                'value' => 'NON DOCS'),
            array('label' => $helper->__('DOCS'),
                'value' => 'DOCS'),
            array('label' => $helper->__('DOCS & NON'),
                'value' => 'DOCS & NON')
        );

        if ($pleaseSelect) {
            array_unshift(
                $options,
                array('label' => $helper->__('- Please Select -'),
                    'value' => ''
                )
            );
        }

        return $options;
    }
}
