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

$installer->addAttribute('catalog_product', 'rw_google_base_skip_submi', array(
    'type' => 'int',
    'input' => 'select',
    'backend' => 'catalog/product_attribute_backend_boolean',
    'source' => 'eav/entity_attribute_source_boolean',
    'label'    => 'Skip from Being Submitted',
    'global'    => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'    => true,
    'required' => 0,
    'user_defined' => false,
    'visible_on_front' => false,
    'used_for_price_rules' => false,
    'position' => 10,
    'default' => 0,
    'group' => 'Google Shopping Feed'
));

$installer->addAttribute('catalog_product', 'rw_google_base_product_type', array(
    'type' => 'varchar',
    'input' => 'text',
    'label'    => 'Product Type',
    'global'    => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'    => true,
    'required' => 0,
    'user_defined' => false,
    'visible_on_front' => false,
    'used_for_price_rules' => false,
    'position' => 20,
    'group' => 'Google Shopping Feed'
));

$installer->addAttribute('catalog_product', 'rw_google_base_product_categ', array(
    'type' => 'varchar',
    'input' => 'text',
    'label'    => 'Product Category',
    'global'    => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'    => true,
    'required' => 0,
    'user_defined' => false,
    'visible_on_front' => false,
    'used_for_price_rules' => false,
    'position' => 30,
    'group' => 'Google Shopping Feed'
));

$installer->addAttribute('catalog_product', 'rw_google_base_12_digit_sku', array(
    'type' => 'varchar',
    'input' => 'text',
    'label'    => '12 Digits Sku',
    'global'    => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'    => true,
    'required' => 0,
    'unique' => 1,
    'user_defined' => false,
    'visible_on_front' => false,
    'used_for_price_rules' => false,
    'position' => 40,
    'group' => 'Google Shopping Feed'
));

$installer->endSetup();