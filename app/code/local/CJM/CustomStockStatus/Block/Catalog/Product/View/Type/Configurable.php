<?php
class CJM_CustomStockStatus_Block_Catalog_Product_View_Type_Configurable extends Mage_Catalog_Block_Product_View_Type_Configurable
{

	public function getAllowProducts()
    {
        if (!$this->hasAllowProducts()) {
            $products = array();
            $allProducts = $this->getProduct()->getTypeInstance(true)->getUsedProducts(null, $this->getProduct());
            
			foreach ($allProducts as $product):
				if(Mage::getStoreConfig('custom_stock/configurableproducts/configoutofstock', Mage::app()->getStore()->getId())):
					$products[] = $product;
				else:
					$qty=(int)$product->getStockItem()->getQty();
					$backordered = $product->getStockItem()->getBackorders();
					$isOutOfStock = $product->getStockItem()->getIsInStock();
                	if ($qty > 0):
						$products[] = $product;
					elseif(($backordered == 1 || $backordered == 2) && $isOutOfStock == 1):
						$products[] = $product;
					endif;
				endif;	
			endforeach;
			$this->setAllowProducts($products);
        }
        return $this->getData('allow_products');
    }
    
    public function getJsonConfig()
    {
        $config = parent::getJsonConfig();
        $config = Mage::helper('core')->jsonDecode($config);
        $childProducts = array();
        
        foreach ($this->getAllowProducts() as $product):
			$info = Mage::helper('customstockstatus')->getTheGoods($product->getId(), $product->getData());
			$alert_link = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB).'productalert/add/stock/product_id/'.$product->getId().'/'.Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED.'/'.Mage::helper('core')->urlEncode($this->getProductUrl($product));
			$childProducts[$product->getId()]["stockstatus"] = $this->__('Availability:').' '.Mage::helper('customstockstatus')->getAvailabilityText($info, 'simple');
			$childProducts[$product->getId()]["configstockstatus"] = Mage::helper('customstockstatus')->getConfigurableStockStatus($info);
			$childProducts[$product->getId()]["shipsby"] = $info['qty'] <= 0 || $info['isInStock'] == 0 ? '' : Mage::helper('customstockstatus')->getShipDateHtml($info, 'simple');
			$childProducts[$product->getId()]["alert"] = Mage::getStoreConfig('custom_stock/configurableproducts/alerts', Mage::app()->getStore()->getId()) && $info['isInStock'] == 0 ? $alert_link : '';
        endforeach;

        $mainInfo = Mage::helper('customstockstatus')->getTheGoods($this->getProduct()->getId(), $this->getProduct()->getData());
        $config['childProducts'] = $childProducts;
		$config['mainstock'] = $this->__('Availability:').' '.Mage::helper('customstockstatus')->getAvailabilityText($mainInfo, 'configurable');
		$config['mainship'] = Mage::helper('customstockstatus')->getShipDateHtml($mainInfo, 'configurable');
		$config['dynamics'] = Mage::getStoreConfig('custom_stock/configurableproducts/dynamics', Mage::app()->getStore()->getId());
		$config['showbottom'] = Mage::getStoreConfig('custom_stock/configurableproducts/bottomavail', Mage::app()->getStore()->getId());
		$config['showship'] = Mage::getStoreConfig('custom_stock/configurableproducts/configurableshowshipdate', Mage::app()->getStore()->getId()) && $mainInfo['ishidden'] == 0 ? "1" : "0";
        return Mage::helper('core')->jsonEncode($config);
    }
}
