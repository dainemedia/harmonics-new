<?php

/**
 * @var Mage_Core_Model_Resource_Setup
 */
$installer = $this;

$installer->startSetup();

$installer->run("

    ALTER TABLE {$this->getTable('sales_flat_quote')}
        ADD COLUMN `firecheckout_delivery_date` DATE DEFAULT NULL,
        ADD COLUMN `firecheckout_delivery_timerange` VARCHAR(13) DEFAULT NULL;

");

$installer->endSetup();
