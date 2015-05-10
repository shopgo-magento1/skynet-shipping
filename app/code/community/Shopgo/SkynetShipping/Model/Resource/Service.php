<?php

class Shopgo_SkynetShipping_Model_Resource_Service extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        $this->_init('skynetshipping/service', 'service_id');
    }

    public function truncate()
    {
        $this->_getWriteAdapter()->query('TRUNCATE TABLE ' . $this->getMainTable());

        Mage::getConfig()->deleteConfig(
            Shopgo_SkynetShipping_Helper_Data::CARRIERS_SKYNET_SYSTEM_PATH . 'service'
        );

        return $this;
    }
}
