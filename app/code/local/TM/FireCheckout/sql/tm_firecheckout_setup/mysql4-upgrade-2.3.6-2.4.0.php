<?php

/**
 * @var Mage_Core_Model_Resource_Setup
 */
$installer = $this;

$installer->startSetup();

$installer->run("

    ALTER TABLE {$this->getTable('sales_flat_order')}
        ADD COLUMN `firecheckout_customer_comment` TEXT DEFAULT NULL AFTER `firecheckout_delivery_timerange`;

    ALTER TABLE {$this->getTable('sales_flat_quote')}
        ADD COLUMN `firecheckout_customer_comment` TEXT DEFAULT NULL AFTER `firecheckout_delivery_timerange`;

");

$installer->endSetup();
