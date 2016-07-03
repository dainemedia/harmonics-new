<?php
//version 101
/**
 * Created by PhpStorm.
 * User: ktree
 * Date: 20/11/14
 * Time: 4:38 PM
 */

$sku = Mage::getStoreConfig('connector/settings/giftcardsku');

if($sku!='')
{
    $websiteId = Mage::app()->getStore()->getWebsiteId();
    $productId = Mage::getModel('catalog/product')->getIdBySku($sku);

    if($productId)
    {
        $product = Mage::getModel('catalog/product')->load($productId);

        if($websiteId)
        {
            $product->setWebsiteIDs(array($websiteId));
            $product->save();

        }
    }
}


/*$storesUrl = $url . '/index.php/api/getInventoryStores'; // prepare url for the rest call



// headers and data (this is API dependent, some uses XML)
$headers = array(
    'Accept: application/json',
    'Content-Type: application/json',
    'X-MBIZPOS-USERNAME: '.$api_user,
    'X-MBIZPOS-PASSWORD: '.$api_key
);

$handle = curl_init();		//curl request to create the product
curl_setopt($handle, CURLOPT_URL, $storesUrl);
curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);


$storesResponse = curl_exec($handle);	// send curl request to microbiz
Mage::log($storesResponse,null,'vesionUpdate.log');
$includeInventoryStoes = json_decode($storesResponse,true);
foreach($includeInventoryStoes as $store) {
    $storeModel = Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->getCollection()->distinct(true)
        ->addFieldToFilter('store_id',$store)->getFirstItem()->getData();
    if($storeModel['id']) {
        $storeModel['include_inventory'] = 1;
        Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->load($storeModel['id'])->setData($storeModel)->save();
    }
}
$code = curl_getinfo($handle);
curl_close($handle);*/

//code to remove the create prod in Mbiz Pos Field in Product View.

$installer = new Mage_Catalog_Model_Resource_Eav_Mysql4_Setup('core_setup');
$installer->startSetup();
$attributeCode='sync_prd_create';
$eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
$attributeId = $eavAttribute->getIdByCode('catalog_product', 'sync_prd_create');
Mage::log("came to data file",null,'datafile.log');
Mage::log("attr Id".$attributeId,null,'datafile.log');
if($attributeId) {
    $attributeSets = Mage::getModel("eav/entity_attribute_set")->getCollection();
    foreach($attributeSets as $attrSet)
    {
        $setId = $attrSet->getAttributeSetId();
        if($setId) {
            Mage::log("attr set id is ".$setId,null,'datafile.log');
            $installer->deleteTableRow('eav/entity_attribute', 'attribute_id', $attributeId, 'attribute_set_id', $setId);
        }

    }
}
$installer->endSetup();