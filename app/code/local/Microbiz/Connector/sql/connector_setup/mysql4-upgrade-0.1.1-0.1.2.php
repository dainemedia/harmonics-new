<?php
//version 102
$installer = $this;

$installer->startSetup();
$installer->run("
CREATE TABLE IF NOT EXISTS {$this->getTable('sync_mbiz_status_tran')}(
	`id` int(11) unsigned NOT NULL  auto_increment,
	`sync_header_id` int(11) unsigned NOT NULL,
    `instance_id` int(2) NOT NULL,
    `sync_status` varchar(50) NOT NULL,
    `status_desc` text,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 COMMENT='Sync Microbiz Instance Update Sync Headers Status';
");

//Removing the Create Product in MBiz Pos Label related Attribute.
$custAttributeType = 'catalog_product';
$productAttrCode = 'sync_prd_create';
$attributeTable = $installer->getAttributeTable('catalog_product', $productAttrCode);
$installer->removeAttribute('catalog_product',$productAttrCode);

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


