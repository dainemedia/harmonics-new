<?php
//version 101
/**
 * Created by PhpStorm.
 * User: ktree
 * Date: 6/2/15
 * Time: 12:06 PM
 */
$installer = $this;
$installer->startSetup();
//code to assign prices attributes to giftcard product type.
$fieldList = array(
    'price','special_price','special_from_date','special_to_date',
    'minimal_price','cost','tier_price','weight'
); //list here all the attribute codes from the price tab
foreach ($fieldList as $field) {
    $applyTo = explode(',', $installer->getAttribute(Mage_Catalog_Model_Product::ENTITY, $field, 'apply_to'));
    if (!in_array('mbizgiftcard', $applyTo)) {
        $applyTo[] = 'mbizgiftcard';
        $installer->updateAttribute(Mage_Catalog_Model_Product::ENTITY, $field, 'apply_to', implode(',', $applyTo));
    }
}

$installer->endSetup();

$frontname = Mage::getConfig()->getNode('admin/routers/adminhtml/args/frontName');
if($frontname != 'admin') {
    $configFile = Mage::getBaseDir().DS.'app/code/local/Microbiz/Connector/etc/config.xml';
    $content=simplexml_load_file($configFile);
    $content->global->rewrite->connector_sales_order_cancel->from = '<![CDATA[#^/'.$frontname.'/sales_order/cancel/#]]>';
    $content->global->rewrite->connector_sales_order_shipment_save->from = '<![CDATA[#^/'.$frontname.'/sales_order_shipment/save/#]]>';
    $content->global->rewrite->connector_catalog_product_save->from = '<![CDATA[#^/'.$frontname.'/catalog_product/save/#]]>';
    $content->global->rewrite->connector_catalog_product_delete->from = '<![CDATA[#^/'.$frontname.'/catalog_product/delete/#]]>';
    $content->global->rewrite->connector_customer_save->from = '<![CDATA[#^/'.$frontname.'/customer/save/#]]>';
    $content->global->rewrite->connector_customer_delete->from = '<![CDATA[#^/'.$frontname.'/customer/delete/#]]>';
    $content->global->rewrite->connector_catalog_category_save->from = '<![CDATA[#^/'.$frontname.'/catalog_category/save/#]]>';
    $content->global->rewrite->connector_catalog_product_action_attribute_save->from = '<![CDATA[#^/'.$frontname.'/catalog_product_action_attribute/save/#]]>';
    $content->global->rewrite->connector_catalog_product_set_save->from = '<![CDATA[#^/'.$frontname.'/catalog_product_set/save/#]]>';
    $content->global->rewrite->connector_catalog_product_attribute_save->from = '<![CDATA[#^/'.$frontname.'/catalog_product_attribute/save/#]]>';
    $content->asXML($configFile);
}