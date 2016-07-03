<?php
//version 113
$installer = $this;

$installer->startSetup();
$installer->run("
CREATE TABLE IF NOT EXISTS {$this->getTable('sal_order_header_tran')}(
	`id` int(11) unsigned NOT NULL auto_increment,
  `order_id` varchar(255) NOT NULL,
  `mbiz_order_id` varchar(255) NOT NULL,
  `order_type` int(11) NOT NULL,
`order_placed_via` int(2) DEFAULT NULL,
  `magento_store_id` int(11) NOT NULL,
  `order_ship_from_store` int(11) NOT NULL,
  `customer_id` varchar(255) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `order_desc` varchar(255) DEFAULT NULL,
  `order_details` text,
  `total_items_count` int(11) DEFAULT NULL,
  `base_amount` decimal(20,4) DEFAULT NULL,
  `total_amount` decimal(20,4) DEFAULT NULL,
  `total_tax` decimal(20,4) DEFAULT NULL,
  `total_deposit` decimal(20,4) DEFAULT NULL,
  `min_deposit_status` tinyint(1) DEFAULT NULL,
  `total_prm_discount` decimal(20,4) DEFAULT NULL,
  `total_general_discount` decimal(20,4) DEFAULT NULL,
  `date_due` date DEFAULT NULL,
  `time_due` time DEFAULT NULL,
  `schedule_date` date DEFAULT NULL,
  `order_service_window` int(11) DEFAULT NULL,
  `priority` varchar(255) DEFAULT NULL,
  `status_id` varchar(50) NOT NULL,
  `overall_shipment_status` int(11) DEFAULT NULL,
  `overall_sync_status` varchar(20) DEFAULT 'Pending',
  `reason_for_cancel` text,
  `assigned_to` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_time` datetime NOT NULL,
  `modified_by` int(11) NOT NULL,
  `modified_time` datetime NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 COMMENT='Sale Order Header Table';

  CREATE TABLE IF NOT EXISTS  {$this->getTable('sal_order_items_tran')} (
    `id` int(11) unsigned NOT NULL auto_increment,
	`order_id` varchar(255) NOT NULL,
  `order_line_item_id` int(11) NOT NULL,
  `mbiz_order_line_item_id` int(11) NOT NULL,
  `order_line_item_type` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `order_ship_from_store` int(11) DEFAULT NULL,
  `order_quantity` double DEFAULT NULL,
  `shipped_quantity` double DEFAULT NULL,
  `taxable` tinyint(1) DEFAULT NULL,
  `prd_tax_class` int(11) DEFAULT NULL,
  `UOM` varchar(15)  DEFAULT NULL,
  `cost_price` decimal(20,4) NOT NULL,
  `unit_price` decimal(20,4) DEFAULT NULL,
  `unit_price_currency` varchar(15)  DEFAULT NULL,
  `item_selling_price` decimal(20,4) NOT NULL,
  `min_deposit_percentage` decimal(20,4) DEFAULT NULL,
  `total_amount` decimal(20,4) DEFAULT NULL,
  `total_tax_amount` decimal(20,4) DEFAULT NULL,
  `total_discount_amount` decimal(20,4) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `shipment_status` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_time` datetime NOT NULL,
  `modified_by` int(11) NOT NULL,
  `modified_time` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

	CREATE TABLE IF NOT EXISTS {$this->getTable('sal_order_brkup_item_tran')} (
`id` int(11) unsigned NOT NULL auto_increment,
  `order_id` varchar(255) NOT NULL,
  `order_line_itm_num` int(11) NOT NULL,
  `mbiz_order_line_itm_num` int(11) NOT NULL,
  `brkup_type_id` int(11) NOT NULL,
  `document_currency` varchar(5) NOT NULL,
  `base_amount` decimal(20,4) DEFAULT NULL,
  `average_cost` decimal(20,4) DEFAULT NULL,
  `selling_price` decimal(20,4) DEFAULT NULL,
  `amount_indicator` tinyint(4) DEFAULT NULL,
  `discount_percent` decimal(20,4) DEFAULT NULL,
  `discount_amount` decimal(20,4) DEFAULT NULL,
  `tax_percent` decimal(20,4) DEFAULT NULL,
  `tax_amount` decimal(20,4) DEFAULT NULL,
  `tax_rule_id` int(15) DEFAULT NULL,
  `tax_rule_name` varchar(50) DEFAULT NULL,
 PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


    ");
// Alter table to add column
if (version_compare(Mage::getVersion(), '1.6.0', '<='))
{
    $installer->getConnection()->addColumn(
        $installer->getTable('eav_attribute_set'),
        'sync_attr_set_create',Varien_Db_Ddl_Table::TYPE_INTEGER,null,
        array(
            'length'    => 1,
            'unsigned'  => true,
            'default' => 0,
            'nullable'  => true,
            'comment'   => 'Sync to Mbiz yes or no'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('catalog_eav_attribute'),
        'sync_attr_create',Varien_Db_Ddl_Table::TYPE_INTEGER,null,
        array(
            'length'    => 1,
            'unsigned'  => true,
            'default' => 0,
            'nullable'  => true,
            'comment'   => 'Sync to Mbiz yes or no'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('eav_attribute'),
        'sync_attr_create',Varien_Db_Ddl_Table::TYPE_INTEGER,null,
        array(
            'length'    => 1,
            'unsigned'  => true,
            'default' => 0,
            'nullable'  => true,
            'comment'   => 'Sync to Mbiz yes or no'
        )
    );
}
else
{
    $installer->getConnection()->addColumn(
        $installer->getTable('eav_attribute_set'),
        'sync_attr_set_create',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'    => 1,
            'unsigned'  => true,
            'default' => 0,
            'nullable'  => true,
            'comment'   => 'Sync to Mbiz yes or no'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('catalog_eav_attribute'),
        'sync_attr_create',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'    => 1,
            'unsigned'  => true,
            'default' => 0,
            'nullable'  => true,
            'comment'   => 'Sync to Mbiz yes or no'
        )
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('eav_attribute'),
        'sync_attr_create',
        array(
            'type'	=> Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length'    => 1,
            'unsigned'  => true,
            'default' => 0,
            'nullable'  => true,
            'comment'   => 'Sync to Mbiz yes or no'
        )
    );
}
$installer->run("

  CREATE TABLE IF NOT EXISTS {$this->getTable('mbiz_attr_grp_rel_mas')} (
	id int(11) unsigned NOT NULL auto_increment,
	instance_id varchar(20) NOT NULL,
    magento_id int(11) unsigned NOT NULL,
    mbiz_id varchar(20) NOT NULL,
	PRIMARY KEY (id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

	CREATE TABLE IF NOT EXISTS {$this->getTable('mbiz_attr_rel_mas')} (
	id int(11) unsigned NOT NULL auto_increment,
	instance_id varchar(20),
    magento_id int(11),
    mbiz_id varchar(20) NOT NULL,
	magento_attr_code varchar(255) NOT NULL,
	mbiz_attr_code varchar(255) NOT NULL,
	mbiz_attr_set_id int(11),
	PRIMARY KEY (id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

	INSERT INTO {$this->getTable('mbiz_attr_rel_mas')} (mbiz_id, magento_attr_code,mbiz_attr_code) VALUES
  (10005, 'name', 'name'),
  (10006, 'description', 'description'),
  (10007, 'short_description', 'short_description'),
  (10008, 'sku', 'sku'),
  (10009, 'weight', 'weight'),
  (10010, 'news_from_date', 'new_from_date'),
  (10011, 'news_to_date', 'new_to_date'),
  (10030, 'status', 'magento_product_status'),
  (10034, 'is_imported', 'is_imported'),
  (10012, 'sync_prd_create', 'prd_create_magento'),
  (10013, 'sync_status', 'prd_sync_status'),
  (10029, 'pos_product_status', 'prd_status'),
  (10004, 'sync_update_msg', 'sync_update_msg'),
  (10016, 'price', 'online_price'),
  (10017, 'tax_class_id', 'prd_tax_class'),
  (10057, 'store_price', 'retail_price'),
  (10038, 'image', 'image'),
  (10039, 'small_image', 'small_image'),
  (10040, 'thumbnail', 'thumbnail');



	  CREATE TABLE IF NOT EXISTS {$this->getTable('mbiz_options_rel_mas')} (
	id int(11) unsigned NOT NULL auto_increment,
	instance_id varchar(20) NOT NULL,
    magento_id int(11) unsigned NOT NULL,
    mbiz_id varchar(20) NOT NULL,
	mbiz_attr_id varchar(255) NOT NULL,
	PRIMARY KEY (id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

	UPDATE {$this->getTable('mbiz_attr_rel_mas')}, {$this->getTable('eav_attribute')}
    SET {$this->getTable('mbiz_attr_rel_mas')}.magento_id = {$this->getTable('eav_attribute')}.attribute_id
    WHERE {$this->getTable('mbiz_attr_rel_mas')}.magento_attr_code = {$this->getTable('eav_attribute')}.attribute_code and {$this->getTable('eav_attribute')}.entity_type_id = 4;



CREATE TABLE IF NOT EXISTS {$this->getTable('mbiz_order_used_shipping_methods')} (
      `id` int(11) unsigned NOT NULL auto_increment,
      `order_id` int(11) NOT NULL,
      `store` varchar(255) NOT NULL default '',
      `delivery_window` int(11),
      `zone_id` int(11),
      `window_type` int(11),
      `delivery_date` varchar(20),
      `note` text,
	  `method` varchar(255) NOT NULL default '',
	  PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS {$this->getTable('mbiz_sales_flat_order_item')} (
	id int(11) unsigned NOT NULL auto_increment,
	order_id int(11) unsigned NOT NULL,
    order_item_id int(11) unsigned NOT NULL,
    product_id int(11) unsigned NOT NULL,
    sku varchar(255) ,
	PRIMARY KEY (id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

	 CREATE TABLE IF NOT EXISTS {$this->getTable('mbiz_category_rel_mas')} (
	id int(11) unsigned NOT NULL auto_increment,
	instance_id varchar(20) NOT NULL,
    magento_id int(11) unsigned NOT NULL,
    mbiz_id varchar(20) NOT NULL,
    is_inventory_category enum('0','1'),
	PRIMARY KEY (id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

	CREATE TABLE IF NOT EXISTS {$this->getTable('mbiz_order_credit_usage_history')} (
	id int(11) unsigned NOT NULL auto_increment,
	credit_id varchar(20) NOT NULL,
	credit_amt decimal (12,4) NOT NULL,
    order_id int(11) unsigned NOT NULL,
    type enum('1','2'),
    status enum('1','2'),
	PRIMARY KEY (id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

	CREATE TABLE IF NOT EXISTS {$this->getTable('mbiz_giftcard_sale_info')} (
	id int(11) unsigned NOT NULL auto_increment,
	order_id int(11) unsigned NOT NULL,
	order_item_id int(11) unsigned NOT NULL,
	gcd_amt decimal (12,4) NOT NULL,
    gcd_type enum('1','2'),
    gcd_unique_num varchar(20) NOT NULL,
    gcd_pin varchar(15) NOT NULL,
	PRIMARY KEY (id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

	ALTER TABLE  {$this->getTable('sync_records_item_tran')} CHANGE  `attribute_value`  `attribute_value` LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

");

/**
* this code is used to add an attribute for category to display a field in the category create form in admin.
 * author: mahesh
*/

$entityTypeId     = $installer->getEntityTypeId('catalog_category');
$attributeSetId   = $installer->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $installer->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);
$installer->addAttribute('catalog_category', 'sync_cat_create',  array(
    'type'     => 'int',
    'label'    => 'Sync to MicroBiz',
    'input'    => 'select',
    'source'   => 'eav/entity_attribute_source_boolean',
    'global'   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'           => true,
    'required'          => false,
    'user_defined'      => false,
    'default'           => 0
));
$installer->addAttributeToGroup(
    $entityTypeId,
    $attributeSetId,
    $attributeGroupId,
    'sync_cat_create',
    '11'                    //last Magento's attribute position in General tab is 10
);
$attributeId = $installer->getAttributeId($entityTypeId, 'sync_cat_create');

//this will set data of your custom attribute for root category
Mage::getModel('catalog/category')
    ->load(1)
    ->setImportedCatId(0)
    ->setInitialSetupFlag(true)
    ->save();
//this will set data of your custom attribute for default category
Mage::getModel('catalog/category')
    ->load(2)
    ->setImportedCatId(0)
    ->setInitialSetupFlag(true)
    ->save();


$installer->updateAttribute('catalog_product','pos_product_status','is_configurable',0);
$installer->updateAttribute('catalog_product','sync_status','is_configurable',0);

//changing the sync settings label and unassigning the attribute from products and customers

/*$attributeCode='sync_prd_create';
$eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
$attributeId = $eavAttribute->getIdByCode('catalog_product', 'sync_prd_create');
if($attributeId) {
    $attributeSets = Mage::getModel("eav/entity_attribute_set")->getCollection();
    foreach($attributeSets as $attrSet)
    {
        $setId = $attrSet->getAttributeSetId();
        $attributes = Mage::getModel('catalog/product_attribute_api')->items($setId);
        foreach($attributes as $_attribute){
            if($_attribute['attribute_id']==$attributeId) {
                Mage::getmodel('Mage_Catalog_Model_Product_Attribute_Set_Api')->attributeRemove($attributeId, $setId);
            }
        }
        //$installer->deleteTableRow('eav/entity_attribute', 'attribute_id', $attributeId, 'attribute_set_id', $setId);

    }
}*/

$custAttributeType = 'customer';
$customerAttrCode = 'sync_cus_create';
$attributeTable = $installer->getAttributeTable('customer', $customerAttrCode);
$installer->removeAttribute('customer',$customerAttrCode);
$installer->addAttribute('customer', 'sync_cus_create', array(
    'type' => 'int',
    'input' => 'boolean',
    'label' => 'Create POS Customer',
    'global' => 1,
    'visible' => 1,
    'required' => 0,
    'user_defined' => 1,
    'default' => '0',
));


$installer->updateAttribute('customer','sync_status','frontend_label','Sync to MicroBiz');
$installer->updateAttribute('catalog_product','sync_status','frontend_label','Sync to MicroBiz');

$installer->endSetup();
$frontname = Mage::getConfig()->getNode('admin/routers/adminhtml/args/frontName');
if($frontname != 'admin') {
    $configFile = Mage::getBaseDir().DS.'app/code/local/Microbiz/Connector/etc/config.xml';
    $content=simplexml_load_file($configFile);
    $content->global->rewrite->connector_sales_order->from = '<![CDATA[#^/'.$frontname.'/sales_order/#]]>';
    $content->global->rewrite->connector_sales_order_shipment->from = '<![CDATA[#^/'.$frontname.'/sales_order_shipment/#]]>';
    $content->global->rewrite->connector_catalog_product_save->from = '<![CDATA[#^/'.$frontname.'/catalog_product/save#]]>';
    $content->global->rewrite->connector_catalog_product_delete->from = '<![CDATA[#^/'.$frontname.'/catalog_product/delete#]]>';
    $content->global->rewrite->connector_customer_save->from = '<![CDATA[#^/'.$frontname.'/customer/save/#]]>';
    $content->global->rewrite->connector_customer_delete->from = '<![CDATA[#^/'.$frontname.'/customer/delete/#]]>';
    $content->global->rewrite->connector_catalog_category_save->from = '<![CDATA[#^/'.$frontname.'/catalog_category/save/#]]>';
    $content->global->rewrite->connector_catalog_product_action_attribute->from = '<![CDATA[#^/'.$frontname.'/catalog_product_action_attribute/save/#]]>';
    $content->global->rewrite->connector_catalog_product_set->from = '<![CDATA[#^/'.$frontname.'/catalog_product_set/#]]>';
    $content->global->rewrite->connector_catalog_product_attribute->from = '<![CDATA[#^/'.$frontname.'/catalog_product_attribute_save/#]]>';
    $content->asXML($configFile);
}