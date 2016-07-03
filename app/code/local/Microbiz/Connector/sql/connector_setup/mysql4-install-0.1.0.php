<?php
//version 103
$installer = $this;

$installer->startSetup();
$installer->run("

CREATE TABLE IF NOT EXISTS {$this->getTable('storeinventory_header')} (
    id int(11) unsigned NOT NULL auto_increment,
	instance_id varchar(20) NOT NULL,
    store_id int(11) NOT NULL,
	company_id int(11) NOT NULL,
	store_name varchar(255) NOT NULL,
	store_short_name varchar(255) NOT NULL,
	company_name  varchar(255) NOT NULL,
    PRIMARY KEY (id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

	CREATE TABLE IF NOT EXISTS {$this->getTable('storeinventory')} (
    storeinventory_id int(11) unsigned NOT NULL auto_increment,
	instance_id varchar(20) NOT NULL,
    material_id int(11) NOT NULL,
    company_id int(2) NOT NULL,
    store_id int(2) NOT NULL,
    quantity int(11) NOT NULL,
    uom varchar(50) DEFAULT 'Each',
    stock_type int(2) DEFAULT '1',
    status tinyint(1) DEFAULT '1',
    created_by int(2) DEFAULT '1',
    created_time datetime DEFAULT NULL,
    modified_by int(2) DEFAULT '1',
    modified_time timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (storeinventory_id),
	UNIQUE KEY (material_id, company_id, store_id )
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS {$this->getTable('prd_shipment_item_tran')} (
    id int(11) unsigned NOT NULL auto_increment,
	shipment_id int(20) NOT NULL,
    material_id int(11) NOT NULL,
    company_id int(2) NOT NULL,
	stock_type int(2) DEFAULT '1',
    store_id int(2) NOT NULL,
    quantity int(11) NOT NULL,
    PRIMARY KEY (id),
	UNIQUE KEY (shipment_id, material_id, company_id, store_id )
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;

 CREATE TABLE IF NOT EXISTS {$this->getTable('prd_shipment_header_tran')} (
    id int(11) unsigned NOT NULL auto_increment,
	shipment_id int(20) NOT NULL,
	material_id int(11) NOT NULL,
	quantity int(11) NOT NULL,
    PRIMARY KEY (id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

    ");
$installer->run("

 CREATE TABLE IF NOT EXISTS {$this->getTable('sync_records_header_tran')} (
    header_id int(11) unsigned NOT NULL auto_increment,
	instance_id varchar(20) NOT NULL,
    model_name varchar(255) NOT NULL,
    obj_id int(11) NOT NULL,
	ref_obj_id int(11),
	mbiz_obj_id varchar(20) DEFAULT NULL,
	mbiz_ref_obj_id varchar(20) DEFAULT NULL,
	associated_configurable_products text DEFAULT NULL,
	obj_status int(11) DEFAULT 1,
	exception_desc text DEFAULT NULL,
	status varchar(20) DEFAULT 'Pending',
    created_by varchar(255) NOT NULL,
    created_time datetime DEFAULT NULL,
    PRIMARY KEY (header_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
    ");
	$installer->run("

CREATE TABLE IF NOT EXISTS {$this->getTable('sync_records_item_tran')} (
	id int(11) unsigned NOT NULL auto_increment,
    header_id int(11) unsigned NOT NULL,
    attribute_name varchar(255) NOT NULL,
    attribute_id int(11) NOT NULL,
	attribute_value text,
    created_by varchar(255) NOT NULL,
    created_time datetime DEFAULT NULL,
    PRIMARY KEY (id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
    ");
	$installer->run("

 CREATE TABLE IF NOT EXISTS {$this->getTable('sync_records_header_tran_history')} (
    id int(11) unsigned NOT NULL  auto_increment,
	header_id int(11) unsigned NOT NULL,
	instance_id varchar(20) NOT NULL,
    model_name varchar(255) NOT NULL,
    obj_id int(11) NOT NULL,
	ref_obj_id int(11),
	mbiz_obj_id varchar(20) DEFAULT NULL,
	mbiz_ref_obj_id varchar(20) DEFAULT NULL,
	associated_configurable_products text DEFAULT NULL,
	obj_status int(11) DEFAULT 1,
	status varchar(20) DEFAULT 'Completed',
    created_by varchar(255) NOT NULL,
    created_time datetime DEFAULT NULL,
    PRIMARY KEY (id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
    ");
	$installer->run("

 CREATE TABLE IF NOT EXISTS {$this->getTable('sync_records_item_tran_history')} (
	id int(11) unsigned NOT NULL auto_increment,
    header_id int(11) unsigned NOT NULL,
    attribute_name varchar(255) NOT NULL,
    attribute_id int(11) NOT NULL,
	attribute_value text,
    created_by varchar(255) NOT NULL,
    created_time datetime DEFAULT NULL,
    PRIMARY KEY (id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
    ");
	$installer->run("

  CREATE TABLE IF NOT EXISTS {$this->getTable('mbiz_cust_rel_mas')} (
	id int(11) unsigned NOT NULL auto_increment,
	instance_id varchar(20) NOT NULL,
    magento_id int(11) unsigned NOT NULL,
    mbiz_id varchar(20) NOT NULL,
	PRIMARY KEY (id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
    ");
	$installer->run("

 CREATE TABLE IF NOT EXISTS {$this->getTable('mbiz_prd_rel_mas')} (
	id int(11) unsigned NOT NULL auto_increment,
	instance_id varchar(20) NOT NULL,
    magento_id int(11) unsigned NOT NULL,
    mbiz_id varchar(20) NOT NULL,
	PRIMARY KEY (id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
    ");
	$installer->run("

  CREATE TABLE IF NOT EXISTS {$this->getTable('mbiz_attr_set_rel_mas')} (
	id int(11) unsigned NOT NULL auto_increment,
	instance_id varchar(20) NOT NULL,
    magento_id int(11) unsigned NOT NULL,
    mbiz_id varchar(20) NOT NULL,
	PRIMARY KEY (id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
    ");
	$installer->run("

  CREATE TABLE IF NOT EXISTS {$this->getTable('mbiz_cust_addr_rel_mas')} (
	id int(11) unsigned NOT NULL auto_increment,
	instance_id varchar(20) NOT NULL,
    magento_id int(11) unsigned NOT NULL,
    mbiz_id varchar(20) NOT NULL,
	PRIMARY KEY (id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

	CREATE TABLE IF NOT EXISTS  {$this->getTable('connector_debug')} (
	id int(11) unsigned NOT NULL auto_increment,
	instance_id varchar(20) NOT NULL,
    status varchar(20),
    status_msg text,
	PRIMARY KEY (id)
	) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;


	CREATE TABLE IF NOT EXISTS {$this->getTable('connector_cataloginventory_stock_item')} LIKE {$this->getTable('cataloginventory_stock_item')};
	INSERT {$this->getTable('connector_cataloginventory_stock_item')} SELECT * FROM {$this->getTable('cataloginventory_stock_item')};

	CREATE TABLE IF NOT EXISTS {$this->getTable('connector_cataloginventory_stock_status')} LIKE {$this->getTable('cataloginventory_stock_status')};
	INSERT {$this->getTable('connector_cataloginventory_stock_status')} SELECT * FROM {$this->getTable('cataloginventory_stock_status')};

    ");
$this->addAttribute('customer', 'sync_cus_create', array(
	'type' => 'int',
	'input' => 'boolean',
	'label' => 'Create POS Customer',
	'global' => 1,
	'visible' => 1,
	'required' => 0,
	'user_defined' => 1,
	'default' => '0',
));

$this->addAttribute('customer', 'sync_status', array(
	'type' => 'int',
	'input' => 'boolean',
	'label' => 'Sync to MicroBiz',
	'global' => 1,
	'visible' => 1,
	'required' => 0,
	'user_defined' => 1,
	'default' => '0',
));


$this->addAttribute('customer', 'pos_cus_status', array(
	'type' => 'int',
	'input' => 'select',
	'label' => 'POS Customer Status',
	'global' => 1,
	'visible' => 1,
	'required' => 0,
	'user_defined' => 1,
	'default' => '1',
	'source' => 'catalog/product_status',
));

$this->addAttribute('customer', 'sync_update_msg', array(
	'type' => 'varchar',
	'input' => 'text',
	'label' => 'POS Update Meassage',
	'global' => 1,
	'visible' => 0,
	'required' => 0,
	'user_defined' => 1,
	'default' => '1',
));


//if (version_compare(Mage::getVersion(), '1.6.0', '<='))
//{
	$customer = Mage::getModel('customer/customer');
	$attrSetId = $customer->getResource()->getEntityType()->getDefaultAttributeSetId();
	$this->addAttributeToSet('customer', $attrSetId, 'General', 'sync_cus_create');
	$this->addAttributeToSet('customer', $attrSetId, 'General', 'sync_status');
	$this->addAttributeToSet('customer', $attrSetId, 'General', 'pos_cus_status');
	$this->addAttributeToSet('customer', $attrSetId, 'General', 'sync_update_msg');
//}

if (version_compare(Mage::getVersion(), '1.4.2', '>='))
{
	Mage::getSingleton('eav/config')
	->getAttribute('customer', 'sync_cus_create')
	->setData('used_in_forms', array('adminhtml_customer','customer_account_create','customer_account_edit'))
	->save();
	Mage::getSingleton('eav/config')
		->getAttribute('customer', 'sync_status')
		->setData('used_in_forms', array('adminhtml_customer','customer_account_create','customer_account_edit'))
		->save();
	Mage::getSingleton('eav/config')
		->getAttribute('customer', 'pos_cus_status')
		->setData('used_in_forms', array('adminhtml_customer','customer_account_create','customer_account_edit'))
		->save();
	Mage::getSingleton('eav/config')
		->getAttribute('customer', 'sync_update_msg')
		->setData('used_in_forms', array('adminhtml_customer','customer_account_create','customer_account_edit'))
		->save();

}

$installer->endSetup();
