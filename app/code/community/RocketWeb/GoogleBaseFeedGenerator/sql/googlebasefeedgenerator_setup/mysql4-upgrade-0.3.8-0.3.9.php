<?php

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 */

/**
 * @category   RocketWeb
 * @package    RocketWeb_GoogleBaseFeedGenerator
 * @author     RocketWeb
 */

/** @var $installer RocketWeb_GoogleBaseFeedGenerator_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;
$installer->startSetup();

// Update old installs to use the "Parent Product Id" directive instead of "Apparel - Item Group Id"
$rows = $installer->getConnection()->fetchAll("SELECT value from `{$this->getTable('core_config_data')}` WHERE path = 'rocketweb_googlebasefeedgenerator/columns/map_product_columns'");

foreach ($rows as $row) {
    $change = false;
    $column_map = unserialize($row['value']);
    foreach ($column_map as $k => $map) {
        if ($map['attribute'] == 'rw_gbase_directive_apparel_item_group_id') {
            $column_map[$k]['attribute'] = 'rw_gbase_directive_parent_id';
            $change = true;
            break;
        }
    }
    if ($change) {
        $installer->getConnection()->update($this->getTable('core_config_data'), array('value' => serialize($column_map)), "path = 'rocketweb_googlebasefeedgenerator/columns/map_product_columns'");
    }
}
$installer->endSetup();