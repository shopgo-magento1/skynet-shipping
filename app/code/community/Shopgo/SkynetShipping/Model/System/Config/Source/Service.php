<?php

class Shopgo_SkynetShipping_Model_System_Config_Source_Service
{
    public function toOptionArray()
    {
        $options    = array();
        $collection = Mage::getModel('skynetshipping/service')->getCollection();

        foreach ($collection as $item) {
            $options[] = array(
                'label' => $item['service'],
                'value' => $item['service_id']
            );
        }

        if (empty($options)) {
            $options[] = array(
                'label' => Mage::helper('skynetshipping')->__('- No Services Available -'),
                'value' => ''
            );
        } else {
            array_unshift(
                $options,
                array(
                    'label' => Mage::helper('skynetshipping')->__('--Please Select--'),
                    'value' => ''
                )
            );
        }

        return $options;
    }
}
