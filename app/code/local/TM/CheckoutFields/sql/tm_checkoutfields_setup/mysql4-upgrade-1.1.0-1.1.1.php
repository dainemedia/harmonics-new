<?php

/**
 * @var Mage_Core_Model_Resource_Setup
 */
$installer = $this;

$installer->startSetup();

$installer->run("

    ALTER TABLE {$this->getTable('sales_flat_quote')}
        ADD COLUMN `tm_field1` TEXT DEFAULT NULL,
        ADD COLUMN `tm_field2` TEXT DEFAULT NULL,
        ADD COLUMN `tm_field3` TEXT DEFAULT NULL,
        ADD COLUMN `tm_field4` TEXT DEFAULT NULL,
        ADD COLUMN `tm_field5` TEXT DEFAULT NULL;

");

$installer->endSetup();
