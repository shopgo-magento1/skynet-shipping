<?php

$installer = $this;

$installer->startSetup();

/**
 * Create table 'sg_skynet_shipping_service'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('skynetshipping/service'))
    ->addColumn('service_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Service ID')
    ->addColumn('service', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable'  => false
        ), 'Service Name')
    ->setComment('Services');

$installer->getConnection()->createTable($table);

$installer->endSetup();
