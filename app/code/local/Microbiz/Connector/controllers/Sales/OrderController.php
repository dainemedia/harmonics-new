<?php
//Version 102
/**
 * Overriding Adminhtml sales order shipment controller
 *
 * @category   Mage
 * @package    microbiz_Connector
 * @author      KT097
 */
include_once("Mage/Adminhtml/controllers/Sales/OrderController.php");
class Microbiz_Connector_Sales_OrderController extends Mage_Adminhtml_Sales_OrderController
{
    /*protected function _construct() {
        Mage::getSingleton('core/session', array('name'=>'adminhtml'));
        if (!(Mage::getSingleton('admin/session') && Mage::getSingleton('admin/session')->isLoggedIn())) {
            header('Location: '.Mage::helper('adminhtml')->getUrl('adminhtml/index/login'));
            exit;
            $this->_forward('adminhtml/index/login');
            return;
        } else {
            parent::_construct();
        }
    }*/

    /**
     * Overriding Cancel order function from core OrderController
     *
     * @return null
	 *@aurhor KT097
     */
    public function cancelAction()
    {
        if ($order = $this->_initOrder()) {
            try {
			$orderid=$order['increment_id'];
			$orderarray = Mage::getModel('sales/order_shipment')->getCollection()->addFieldToFilter('increment_id', $orderid)->getFirstItem()->getData();
				$order->cancel()
                    ->save();
                $this->_getSession()->addSuccess(
                    $this->__('The order has been cancelled.')
                );
				$count=count($orderarray);
				if($count) {
								$sipmentid=$orderarray['order_id'];
								$returnproductcollection = Mage::getModel('connector/storeinventoryproduct_storeinventoryproduct')->getCollection()->addFieldToFilter('shipment_id', $sipmentid)->getData();
								//print_r($returnproductcollection);
								$inventoryresult=array();
									foreach($returnproductcollection as $returnproduct) {
									$inventorydetails=array();
										$companyId=$returnproduct['company_id'];
										$materialId=$returnproduct['material_id'];
										$store_id=$returnproduct['store_id'];
										$qty=$returnproduct['quantity'];
										$inventorydetails['store_id']=$storeId;
									$inventorydetails['material_id']=$materialId;
									$inventorydetails['company_id']=$companyId;
									$inventorydetails['quantity']=$qty;
									$inventorydetails['movement_indigator']='+';
									$inventoryresult[]=$inventorydetails;
										if($companyId){
											$model = Mage::getModel('connector/storeinventory_storeinventory')->getCollection()->addFieldToFilter('material_id', $materialId)->addFieldToFilter('store_id', $storeId)->addFieldToFilter('company_id', $companyId)->getFirstItem();
										}
										else {
											$model = Mage::getModel('connector/storeinventory_storeinventory')->getCollection()->addFieldToFilter('material_id', $materialId)->addFieldToFilter('store_id', $storeId)->getFirstItem();
										}
										$inventoryinfo=$model->toArray(); //storing the inventory information into array
									//print_r($inventoryinfo); 
									$inventoryId=$inventoryinfo['storeinventory_id']; 
									$inventoryinfo['quantity']=$inventoryinfo['quantity'] + $qty; //updating the quantity in inventory information array
									$model = Mage::getModel('connector/storeinventory_storeinventory')->load($inventoryId)->setData($inventoryinfo); //setting the inventory data based on stock inventory ID
									$model->setId($inventoryId)->save();   
									}
									$url = Mage::getStoreConfig('connector/settings/api_server');	// get microbiz server details fron configuration settings. 
								$url    = $url.'/index.php/api/inventoryMovement';			// prepare url for the rest call
								$api_user = Mage::getStoreConfig('connector/settings/api_user');
								$api_key = Mage::getStoreConfig('connector/settings/api_key');
								$method = 'POST';
								// headers and data (this is API dependent, some uses XML)
								$headers = array(
									'Accept: application/json',
									'Content-Type: application/json',
									'X-MBIZPOS-USERNAME: '.$api_user,
									'X-MBIZPOS-PASSWORD: '.$api_key
								);
								$invdata    = json_encode($inventoryresult);
								Mage::log($invdata);
							//ini_set('display_errors','1');
						$handle = curl_init();
						curl_setopt($handle, CURLOPT_URL, $url);
						curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
						curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
						curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
						
						switch ($method) {    
							case 'GET':
								break;
							
							case 'POST':
								curl_setopt($handle, CURLOPT_POST, true);
								curl_setopt($handle, CURLOPT_POSTFIELDS, $invdata);
								break;
							
							case 'PUT':
								curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PUT');
								curl_setopt($handle, CURLOPT_POSTFIELDS, $invdata);
								break;
							
							case 'DELETE':
								curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
								break;
						} // $method
						
						$response = curl_exec($handle);	
						$code = curl_getinfo($handle);

						// Mage::getSingleton('core/session')->addError($code . "  : " . $response);

						// if($code['http_code'] == 500 ) Mage::getSingleton('core/session')->addError($response);
						if($code['http_code'] == 200 ) { //Mage::getSingleton('core/session')->addSuccess($response); 
						}
						else
						Mage::getSingleton('core/session')->addError($response);
						$result = json_decode($response, true);
						//print_r($result);
						//exit;
					foreach($result as $res ) {
							$qty=$res['quantity'];
							$storeId=$res['store_id'];
							$materialId=$res['material_id'];
							$companyId=$res['company_id']; //assigning value into companyId
							if($companyId ){
									$model = Mage::getModel('connector/storeinventory_storeinventory')->getCollection()->addFieldToFilter('material_id', $materialId)->addFieldToFilter('store_id', $storeId)->addFieldToFilter('company_id', $companyId)->getFirstItem();
								}
								else {
									$model = Mage::getModel('connector/storeinventory_storeinventory')->getCollection()->addFieldToFilter('material_id', $materialId)->addFieldToFilter('store_id', $storeId)->getFirstItem();
								}
								$inventoryinfo=$model->toArray(); //storing the inventory information into array
								$inventoryId=$inventoryinfo['storeinventory_id']; 
								$inventoryinfo['quantity']=$qty; //updating the quantity in inventory information array
								$model = Mage::getModel('connector/storeinventory_storeinventory')->load($inventoryId)->setData($inventoryinfo); //setting the inventory data based on stock inventory ID
								$model->setId($inventoryId)->save();   
					}
				}
				
			   
            }
            catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
            catch (Exception $e) {
                $this->_getSession()->addError($this->__('The order has not been cancelled.'));
                Mage::logException($e);
            }
            $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
        }
    }


    public function syncOrderToMicroBizAction() {
        $order = $this->_initOrder();
        $orderId = $order->getId();
        $order = Mage::getModel('sales/order')->load($orderId);
        Mage::getModel('Microbiz_Connector_Model_Observer')->onOrderSave($order);
        $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
    }

    
}
