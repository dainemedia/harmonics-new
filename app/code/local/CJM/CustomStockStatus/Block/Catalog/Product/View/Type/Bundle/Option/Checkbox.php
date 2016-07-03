<?php
class CJM_CustomStockStatus_Block_Catalog_Product_View_Type_Bundle_Option_Checkbox
    extends Mage_Bundle_Block_Catalog_Product_View_Type_Bundle_Option_Checkbox
	{
	public function _construct()
    	{
		$storeId = Mage::app()->getStore()->getId();
		if(Mage::getStoreConfig('custom_stock/bundleproducts/enabled', $storeId)):
			$this->setTemplate('customstock/bundle/catalog/product/view/type/bundle/option/checkbox.phtml');
		else:
			$this->setTemplate('bundle/catalog/product/view/type/bundle/option/checkbox.phtml');
		endif;
    	}	
}