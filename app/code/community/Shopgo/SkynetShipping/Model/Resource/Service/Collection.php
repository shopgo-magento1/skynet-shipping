<?php

class Shopgo_SkynetShipping_Model_Resource_Service_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('skynetshipping/service');
    }
}
