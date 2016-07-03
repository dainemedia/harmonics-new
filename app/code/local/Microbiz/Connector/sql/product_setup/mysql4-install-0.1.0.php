<?php
//version 101
$installer = $this;
 
$installer->startSetup();


$this->addAttribute('catalog_product', 'sync_prd_create', array(
	'type' => 'varchar',
	'input' => 'boolean',
	'label' => 'Create Product in MBiz POS',
	'global' => 1,
	'visible' => 1,
	'required' => 0,
	'user_defined' => 0,
	'default' => '0',
));

$this->addAttribute('catalog_product', 'sync_status', array(
	'type' => 'int',
	'input' => 'boolean',
	'label' => 'Sync to MicroBiz',
	'global' => 1,
	'visible' => 1,
	'required' => 0,
	'user_defined' => 0,
	'default' => '0',
));

$this->addAttribute('catalog_product', 'pos_product_status', array(
	'type' => 'int',
	'input' => 'select',
	'label' => 'MBiz POS Product Status',
	'global' => 1,
	'visible' => 1,
	'required' => 0,
	'user_defined' => 0,
	'default' => '1',
        'source' => 'catalog/product_status',
));

$this->addAttribute('catalog_product', 'sync_update_msg', array(
	'type' => 'varchar',
	'input' => 'text',
	'label' => 'Magento MBiz POS Update',
	'global' => 1,
	'visible' => 0,
	'required' => 0,
	'user_defined' => 1,
	'default' => '',
));

$this->addAttribute('catalog_product', 'store_price', array(
	'backend' => 'catalog/product_attribute_backend_price',
	'type' => 'decimal',
	'input' => 'price',
	'label' => 'Store Price',
	'global' => 1,
	'visible' => 1,
	'required' => 0,
	'user_defined' => 0,
	'default' => '',
));

// if (version_compare(Mage::getVersion(), '1.6.0', '<='))
//{
	//$model=Mage::getModel('eav/entity_setup','core_setup');

	/* $attrSetId=$this->getAttributeSetId('catalog_product','Default');
	$attributeGeneralId=$this->getAttributeGroup('catalog_product',$attributeSetId,'General');
	$attributePriceId=$this->getAttributeGroup('catalog_product',$attributeSetId,'Prices'); */

	//Mage::log($attrSetId. 'attrSetId');
	//Mage::log($attributeGeneralId. 'attributeGeneralId');
	//Mage::log($attributePriceId. 'attributePriceId');

	      $product = Mage::getModel('catalog/product');
	      $attrSetId = $product->getResource()->getEntityType()->getDefaultAttributeSetId();

        $this->addAttributeToSet('catalog_product', $attrSetId, 'General', 'sync_prd_create');
	$this->addAttributeToSet('catalog_product', $attrSetId, 'General', 'sync_status');
	$this->addAttributeToSet('catalog_product', $attrSetId, 'General', 'pos_product_status');
	$this->addAttributeToSet('catalog_product', $attrSetId, 'General', 'sync_update_msg');
        $this->addAttributeToSet('catalog_product', $attrSetId, 'Prices', 'store_price');
//}
$installer->updateAttribute('catalog_product','pos_product_status','is_configurable',0);
$installer->updateAttribute('catalog_product','sync_status','is_configurable',0);
$installer->endSetup();
