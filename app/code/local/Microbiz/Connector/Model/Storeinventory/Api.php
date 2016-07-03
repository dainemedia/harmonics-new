<?php
//version 104
class Microbiz_Connector_Model_Storeinventory_Api extends Mage_Api_Model_Resource_Abstract
{

    /**
     *Global Function to Create Inventory
     *@params inventoryData
     *@return inventoryId
     *@aurhor KT097
     */
    public function createProductsInventory($inventoryInfo) {
      try {
            foreach($inventoryInfo as $productId=>$inventory) {
                Mage::getModel('Microbiz_Connector_Model_Storeinventory_Api')->createMbizInventory($inventory, $productId);
            }
        }
        catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());

        }

        return true;
    }

    /**
     *Global Function to Create Inventory
     *@params inventoryData
     *@return inventoryId
     *@aurhor KT097
     */
    public function createMbizInventory($inventory, $productId = null)
    {
			// $storeInventories = array();
			Mage::Log('Storeinventory import start');
			Mage::Log($inventory);
			Mage::Log('Storeinventory import end');
            try {
			    foreach ($inventory as $inventoryData) {
				// Check if Inventory is already exist for this product
                    $inventoryData['material_id'] =  ($productId) ? $productId : $inventoryData['material_id'];
                    $inventoryData['stock_type'] = (isset($inventoryData['stock_type'])) ? $inventoryData['stock_type'] : 1;
                $productInventory = Mage::getModel('connector/storeinventory_storeinventory')->getCollection()->addFieldToFilter('material_id', $inventoryData['material_id'])->addFieldToFilter('company_id', $inventoryData['company_id'])->addFieldToFilter('store_id', $inventoryData['store_id'])->addFieldToFilter('stock_type', $inventoryData['stock_type'])->getData();
				$inventoryData['instance_id']=Mage::helper('microbiz_connector')->getAppInstanceId();
				// if Not Create Store Inventory else update the inventory. 
				if(empty($productInventory))
				{
				$model  = Mage::getModel('connector/storeinventory_storeinventory')->setData($inventoryData)->save();
				}
				else{
				$inventoryId = $productInventory[0]['storeinventory_id']; //assinging id into inventoryId
				$model       = Mage::getModel('connector/storeinventory_storeinventory')->load($inventoryId)->setData($inventoryData); //setting the inventory data based on stock inventory ID
				$model->setId($inventoryId)->save(); //saving the model
				}
				// Check if store exists, create/update store in the magento 
                $storemodel = Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->getCollection()->addFieldToFilter('company_id', $inventoryData['company_id'])->addFieldToFilter('store_id', $inventoryData['store_id'])->getData();
				
                if (!count($storemodel)) {
                    $storeinformation                     = array();
                    $storeinformation['store_id']         = $inventoryData['store_id'];
                    $storeinformation['company_id']       = $inventoryData['company_id'];
                    $storeinformation['store_name']       = $inventoryData['store_name'];
                    $storeinformation['company_name']     = $inventoryData['company_name'];
                    $storeinformation['store_short_name'] = $inventoryData['store_short_name'];
					$storeinformation['instance_id']=Mage::helper('microbiz_connector')->getAppInstanceId();
                    $model1                               = Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->setData($storeinformation)->save();
                }
				    $inventoryIds[] = $model->getId();
			}
			/*$trashInventory = Mage::getModel('connector/storeinventory_storeinventory')->getCollection()->addFieldToFilter('material_id', $productId)->addFieldToFilter('storeinventory_id', array('nin' => $inventoryIds))->load();
			foreach($trashInventory as $trash){
			$trash->delete();
			}*/
                if($productId) {
                    $storemodel = Mage::getModel('connector/storeinventorytotal_storeinventorytotal')->getCollection()->addFieldToFilter('include_inventory', 1)->getColumnValues('store_id');

                    $productTotalInventory = Mage::getModel('connector/storeinventory_storeinventory')
                        ->getCollection()->addFieldToFilter('store_id',array('in'=>$storemodel))
                        ->addFieldToFilter('material_id', $productId);
                    $productTotalInventoryData = $productTotalInventory->getColumnValues('quantity');
                    $totalQuantity =  array_sum($productTotalInventoryData);

                    if($productTotalInventoryData) {

                        $materialId = $productId;
                        $qty = $totalQuantity;

                        $stockItem   = Mage::getModel('cataloginventory/stock_item')->loadByProduct($materialId);

                        $stockItem->setData('qty', $qty);
                        if ($qty > 0) {
                            $stockItem->setData('is_in_stock', '1');
			}
                        else {
                            $stockItem->setData('is_in_stock', '0');
                        }

                        $stockItem->save();


                    }
                }

            }
            catch (Mage_Core_Exception $e) {
                $this->_fault('data_invalid', $e->getMessage());
                // We cannot know all the possible exceptions,
                // so let's try to catch the ones that extend Mage_Core_Exception
            }
            catch (Exception $e) {
                $this->_fault('data_invalid', $e->getMessage());
            }		
        return true;
    }
	
    //End of global function to create inventory data
    /**
     *Global Function to get Inventory info
     *@params data
     *@return array of information of perticular stock id
     *@aurhor KT097
     */
    public function infoMbizInventory($data)
    {
        //$data=array('store_id'=>1,'material_id'=>1);
        $inventoryId = $data['storeinventory_id'];
        if ($inventoryId) {
            $model = Mage::getModel('connector/storeinventory_storeinventory')->load($inventoryId);
            if (!$model->getId()) {
                $this->_fault('not_exists');
                // If stockid not found.
            }
            return $model->toArray();
            
        }
        
        else {
            
            $model = Mage::getModel('connector/storeinventory_storeinventory')->getCollection();
            
            // addFieldToFilter get data based on the field we passed
            $model->addFieldToFilter('store_id', $data['store_id']);
            
            $model->addFieldToFilter('material_id', $data['material_id']);
            
            $model->addFieldToFilter('stock_type', $data['stock_type']);
            
            return $model->toArray();
        }
    }
    //End of global function to get inventory info
    /**
     *Global Function to update Inventory 
     *@params inventoryId and Inventorydata array
     *@return true on success
     *@aurhor KT097
     */
    public function updateMbizInventory($inventorysData)
    {
        $inventorysData = array(
            $inventorysData
        );
        foreach ($inventorysData as $inventoryData) {
            $storeId    = $inventoryData['store_id']; //assigning value into storeId
            $materialId = $inventoryData['material_id']; //assigning value into materialId
            $companyId  = $inventoryData['company_id']; //assigning value into companyId
			$inventoryData['instance_id']=Mage::helper('microbiz_connector')->getAppInstanceId();
            if ($companyId) {
                $model = Mage::getModel('connector/storeinventory_storeinventory')->getCollection()->addFieldToFilter('material_id', $materialId)->addFieldToFilter('stock_type', $inventoryData['stock_type'])->addFieldToFilter('store_id', $storeId)->addFieldToFilter('company_id', $companyId)->getFirstItem();
            } else {
                $model = Mage::getModel('connector/storeinventory_storeinventory')->getCollection()->addFieldToFilter('material_id', $materialId)->addFieldToFilter('store_id', $storeId)->addFieldToFilter('stock_type', $inventoryData['stock_type'])->getFirstItem();
            }
            
            if (!$model->getId()) {
                // $this->_fault('not_exists');
                return false;
                // If stockid not found.
            }
            $inventoryinfo = $model->toArray(); //storing the inventory information into array
            
            $inventoryId = $inventoryinfo['storeinventory_id']; //assinging id into inventoryId 
            $model       = Mage::getModel('connector/storeinventory_storeinventory')->load($inventoryId)->setData($inventoryData); //setting the inventory data based on stock inventory ID
            $model->setId($inventoryId)->save(); //saving the model
        }
        return true;
    }
    //End of global function to update data
    /**
     *Global Function to delete Inventory 
     *@params inventoryId
     *@return true on success
     *@aurhor KT097
     */
    public function deleteMbizInventory($inventoryId)
    {
        //load the stock inventory model based on the inventoryId
        $model = Mage::getModel('connector/storeinventory_storeinventory')->load($inventoryId);
        
        if (!$model->getId()) {
            $this->_fault('not_exists');
            // No inventoryId found
        }
        
        try {
            $model->delete();
        }
        catch (Mage_Core_Exception $e) {
            $this->_fault('not_deleted', $e->getMessage());
            // Some errors while deleting.
        }
        
        return true;
    }
    //End of global function to delete inventory data
    
    /**
     *Global Function to reduce Mbiz Inventory 
     *@params Inventorydata array
     *@return true on success
     *@aurhor KT097
     */
    public function reduceMbizInventory($inventoryData)
    {
        
        $storeId    = $inventoryData['store_id']; //assigning value into storeId
        $materialId = $inventoryData['material_id']; //assigning value into materialId
        $companyId  = $inventoryData['company_id']; //assigning value into companyId
        if ($companyId) {
            $model = Mage::getModel('connector/storeinventory_storeinventory')->getCollection()->addFieldToFilter('material_id', $materialId)->addFieldToFilter('store_id', $storeId)->addFieldToFilter('company_id', $companyId)->getFirstItem();
        } else {
            $model = Mage::getModel('connector/storeinventory_storeinventory')->getCollection()->addFieldToFilter('material_id', $materialId)->addFieldToFilter('store_id', $storeId)->getFirstItem();
        }
        
        if (!$model->getId()) {
            $this->_fault('not_exists');
            // If stockid not found.
        }
        $inventoryinfo = $model->toArray(); //storing the inventory information into array
        
        $inventoryId               = $inventoryinfo['storeinventory_id']; //assinging id into inventoryId 
        //updating the quantity in inventoryinfo array
        $inventoryinfo['quantity'] = $inventoryinfo['quantity'] - $inventoryData['quantity'];
        $model                     = Mage::getModel('connector/storeinventory_storeinventory')->load($inventoryId)->setData($inventoryinfo); //setting the inventory data based on stock inventory ID
        $model->setId($inventoryId)->save(); //saving the model
        return true;
        
    }
    //End of global function to reduceInventoryData
    
    /**
     *Global Function to raise Mbiz Inventory 
     *@params Inventorydata array
     *@return true on success
     *@aurhor KT097
     */
    public function raiseMbizInventory($inventoryData)
    {
        
        $storeId    = $inventoryData['store_id']; //assigning value into storeId
        $materialId = $inventoryData['material_id']; //assigning value into materialId
        $companyId  = $inventoryData['company_id']; //assigning value into companyId
        if ($companyId) {
            $model = Mage::getModel('connector/storeinventory_storeinventory')->getCollection()->addFieldToFilter('material_id', $materialId)->addFieldToFilter('store_id', $storeId)->addFieldToFilter('company_id', $companyId)->getFirstItem();
        } else {
            $model = Mage::getModel('connector/storeinventory_storeinventory')->getCollection()->addFieldToFilter('material_id', $materialId)->addFieldToFilter('store_id', $storeId)->getFirstItem();
        }
        
        if (!$model->getId()) {
            $this->_fault('not_exists');
            // If stockid not found.
        }
        $inventoryinfo             = $model->toArray(); //storing the inventory information into array
        $inventoryinfo['quantity'] = $inventoryinfo['quantity'] + $inventoryData['quantity']; //updating the quantity in inventoryinfo array
        $inventoryId               = $inventoryinfo['storeinventory_id'];
        $model                     = Mage::getModel('connector/storeinventory_storeinventory')->load($inventoryId)->setData($inventoryinfo); //setting the inventory data based on stock inventory ID
        $model->setId($inventoryId)->save(); //saving the model
        return true;
        
    }
    //End of global function to raiseInventoryData
}
?>