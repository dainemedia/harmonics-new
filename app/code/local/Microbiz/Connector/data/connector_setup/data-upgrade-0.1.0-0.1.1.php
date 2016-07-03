<?php
//version 107
/*$sku = Mage::getStoreConfig('connector/settings/giftcardsku');
$websiteId = Mage::app()->getStore()->getWebsiteId();
$storeId = Mage::app()->getStore()->getStoreId();
$product = Mage::getModel('catalog/product');
$attrSetId = $product->getResource()->getEntityType()->getDefaultAttributeSetId();
if($sku=='')   // if sku is not available or empty create new product
{
    $uniqueString = Mage::helper('microbiz_connector')->mbizGenerateUniqueString(6);
    $sku = 'MBIZ_GC_'.$uniqueString;
    $productId = Mage::getModel('catalog/product')->getIdBySku($sku);
    if($productId)  // if the product available with the newly generated sku then again sku will be generated
    {
        $uniqueString = Mage::helper('microbiz_connector')->mbizGenerateUniqueString(6);
        $sku = 'MBIZ_GC_'.$uniqueString;
    }

    $product = new Mage_Catalog_Model_Product();
// Build the product
    $product->setSku($sku);
    $product->setAttributeSetId($attrSetId);
    $product->setTypeId('virtual');
    $product->setName('MBIZ Gift Card');
    $product->setDescription('This is a Microbiz Gift Card Product');
    $product->setShortDescription('This is a Microbiz Gift Card Product');
    $product->setPrice(0); # Set some price
    $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
    $product->setStatus(1);
    $product->setTaxClassId(0); # My default tax class
    $product->setWebsiteIDs(array($websiteId));
    $product->setStoreIDs(array($storeId));
    $product->setCreatedAt(strtotime('now'));
    $product->setStockData(array(
        'is_in_stock' => 1,
        'qty' => 99999
    ));
    try {
        $product->save();
        $configData = new Mage_Core_Model_Config();
        $configData->saveConfig('connector/settings/giftcardsku',$sku,'default','');
        //$configData->saveConfig('connector/settings/showleft','1','default','');
    }
    catch (Exception $ex) {
       Mage::log($e->getMessage());
    }
}

else{
    $productId = Mage::getModel('catalog/product')->getIdBySku($sku);
    if(!$productId) // if the product is not available with the sku present in config data we will create new prod.
    {
        $product = new Mage_Catalog_Model_Product();
// Build the product
        $product->setSku($sku);
        $product->setAttributeSetId($attrSetId);
        $product->setTypeId('virtual');
        $product->setName('MBIZ Gift Card');
        $product->setDescription('This is a Microbiz Gift Card Product');
        $product->setShortDescription('This is a Microbiz Gift Card Product');
        $product->setPrice(0); # Set some price
        $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
        $product->setStatus(1);
        $product->setTaxClassId(0); # My default tax class
        $product->setWebsiteIDs(array($websiteId));
        $product->setStoreIDs(array($storeId));
        $product->setCreatedAt(strtotime('now'));
        $product->setStockData(array(
            'is_in_stock' => 1,
            'qty' => 99999
        ));
        try {
            $product->save();
            $configData = new Mage_Core_Model_Config();
            $configData->saveConfig('connector/settings/giftcardsku',$sku,'default','');
        }
        catch (Exception $ex) {
            Mage::log($ex->getMessage());
        }
    }
}*/