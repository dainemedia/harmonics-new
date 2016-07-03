<?php
//Version 103
/**
 * Overriding Adminhtml sales order shipment controller
 *
 * @category   Mage
 * @package    microbiz_Connector
 * @author      KT097
 */
include_once("Mage/Adminhtml/controllers/Sales/Order/ShipmentController.php");
class Microbiz_Connector_Sales_Order_ShipmentController extends Mage_Adminhtml_Sales_Order_ShipmentController
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
     * Overriding Save shipment function from core shipmentcontroller
     * We can save only new shipment. Existing shipments are not editable
     *
     * @return null
     *@aurhor KT097
     */
    public function saveAction()
    {

        if(!Mage::getStoreConfig('connector/settings/allowstoreselection')){
            return parent::saveAction();

        }

        $data   =$this->getRequest()->getPost('shipment');
        //print_r($data); exit;
        $display='';
        if(!empty($data['comment_text'])) {
            Mage::getSingleton('adminhtml/session')->setCommentText($data['comment_text']);
        }
        
        try {

            /* KT097 added code for reduce shipping quantities from Mbiz storeinventory table */
            $salOderModelInfo = Mage::getModel('saleorderheader/saleorderheader')->getCollection()->addFieldToFilter('order_id', $this->getRequest()->getParam('order_id'))->setOrder('id','asc')->getData();
            if(count($salOderModelInfo)){
                $orderType = $salOderModelInfo[0]['order_type'];
                if($orderType == 4 && count($data['storeitems'])) {
                    foreach($data['storeitems'] as $key=>$value) {
                        $productShipFromStore                      =array();
                        $qty                                   =$value;
                        $storeprodid                           =explode("-", $key);
                        $storeId                               =$storeprodid[0];
                        $materialId                            =$storeprodid[1];
                        $productShipFromStore[$materialId]=$storeId;
                    }
                }
                return parent::saveAction();
            }
            $shipment=$this->_initShipment();
            //print_r($shipment); exit;
            if(!$shipment) {
                $this->_forward('noRoute');
                return;
            }
            if(!empty($data['storeitems'])) {
                $storeitems     =$data['storeitems'];
                $inventoryresult=array();
                foreach($storeitems as $key=>$value) {
                    $inventorydetails                      =array();
                    $qty                                   =$value;
                    $storeprodid                           =explode("-", $key);
                    $storeId                               =$storeprodid[0];
                    $materialId                            =$storeprodid[1];
                    $companyId                             =$storeprodid[2]; //assigning value into companyId
					$shipmentId				   = $shipment->getOrderId();
					/*KT097 added code for check store inventory of a product*/
					if($companyId) {
                        $model=Mage::getModel('connector/storeinventory_storeinventory')->getCollection()->addFieldToFilter('material_id', $materialId)->addFieldToFilter('store_id', $storeId)->addFieldToFilter('company_id', $companyId)->getFirstItem();
                    } else {
                        $model=Mage::getModel('connector/storeinventory_storeinventory')->getCollection()->addFieldToFilter('material_id', $materialId)->addFieldToFilter('store_id', $storeId)->getFirstItem();
                    }
                    $inventoryinfo            =$model->toArray(); //storing the inventory information into array
					if(!Mage::getStoreConfig('connector/settings/allownegativeinv') &&$inventoryinfo['quantity']<$qty) {
						Mage::getSingleton('core/session')->addError("For Some Product Required Quantity Is not available");
						$this->_redirect('*/sales_order/view', array(
							'order_id'=>$shipment->getOrderId()
						));
						return;
					}
					/*KT097 added code for check store inventory of a product END*/
                    $inventorydetails['store_id']          = $storeId;
                    $inventorydetails['material_id']       = $materialId;
                    $inventorydetails['company_id']        = $companyId;
                    $inventorydetails['quantity']          = $qty;
                    $inventorydetails['shipment']          = $shipmentId;
                    $inventorydetails['movement_indigator']='-';
                    $inventoryresult[]                     =$inventorydetails;
                    
                    $inventoryId              =$inventoryinfo['storeinventory_id'];
                    $inventoryinfo['quantity']=$inventoryinfo['quantity']-$qty; //updating the quantity in inventory information array
                    $model                    =Mage::getModel('connector/storeinventory_storeinventory')->load($inventoryId)->setData($inventoryinfo); //setting the inventory data based on stock inventory ID
                    $model->setId($inventoryId)->save();
                    
                    //        $display.=$qty.'A'.$storeId.'A'.$materialId.'A'.$inventoryinfo['quantity'].'A';
                }
				$apiInformation=Mage::helper('microbiz_connector')->getApiDetails();
                $url    =$apiInformation['api_server']; // get microbiz server details fron configuration settings. 
                $url    =$url . '/index.php/api/inventoryMovement'; // prepare url for the rest call
				$api_user = $apiInformation['api_user'];
				$api_key = $apiInformation['api_key'];
                $method ='POST';
                // headers and data (this is API dependent, some uses XML)
                $headers=array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'X-MBIZPOS-USERNAME: '.$api_user,
					'X-MBIZPOS-PASSWORD: '.$api_key
                );
                $invdata=json_encode($inventoryresult);
                Mage::log($invdata);
                //ini_set('display_errors','1');
                $handle=curl_init();
                curl_setopt($handle, CURLOPT_URL, $url);
                curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
                
                switch($method) {
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
                
                $response=curl_exec($handle);
                $code    =curl_getinfo($handle);
                
                // Mage::getSingleton('core/session')->addError($code . "  : " . $response);
                
                // if($code['http_code'] == 500 ) Mage::getSingleton('core/session')->addError($response);
                if($code['http_code']==200) { //Mage::getSingleton('core/session')->addSuccess($response); 
                } else {
                    Mage::getSingleton('core/session')->addError($response);
                    return;
                }
                //$result=json_decode($response, true);


            }
            /* KT097 added code end for reduce shipping quantities from Mbiz storeinventory table */
            $shipment->register();
            $comment='';
            if(!empty($data['comment_text'])) {
                $shipment->addComment($data['comment_text'], isset($data['comment_customer_notify']), isset($data['is_visible_on_front']));
                if(isset($data['comment_customer_notify'])) {
                    $comment=$data['comment_text'];
                }
            }
            
            if(!empty($data['send_email'])) {
                $shipment->setEmailSent(true);
            }
            
            $shipment->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
            $responseAjax     =new Varien_Object();
            $isNeedCreateLabel=isset($data['create_shipping_label'])&&$data['create_shipping_label'];
            
            if($isNeedCreateLabel&&$this->_createShippingLabel($shipment)) {
                $responseAjax->setOk(true);
            }
            
            $this->_saveShipment($shipment);
            /*  KT097 added code for Saving shipped Items details */
            /*ini_set('display_errors','1');*/
            $totalinventoryprod=array();
            $count             =0;
            $individualprod               =array();
            $productidval = '';
            foreach($inventoryresult as $productinventory) {
                if($count>0) {
                    if($individualprod['material_id']!=$productinventory['material_id']) {
                        $totalinventoryprod[]=$individualprod;
                    }
                }
                if($productidval!=$productinventory['material_id']) {

                    $productidval                 =$productinventory['material_id'];
                    $totalquantity                =0;
                    $individualprod['shipment_id']=$shipment->getOrderId();
                    $individualprod['material_id']=$productidval;
                }
                $productinventory['shipment_id']=$shipment->getOrderId();
                $model                          =Mage::getModel('connector/storeinventoryproduct_storeinventoryproduct')->setData($productinventory)->save();
                $totalquantity                  =$totalquantity+$productinventory['quantity'];
                $individualprod['quantity']     =$totalquantity;
                $count++;
            }
            if($individualprod['material_id']==$productinventory['material_id']) {
                $totalinventoryprod[]=$individualprod;
            }
            foreach($totalinventoryprod as $totalinventoryproduct) {
                $model=Mage::getModel('connector/storeinventoryproducttotal_storeinventoryproducttotal')->setData($totalinventoryproduct)->save();
            }
            
            /*  KT097 added code for Saving shipped Items details */
            $shipment->sendEmail(!empty($data['send_email']), $comment);
            
            $shipmentCreatedMessage=$this->__('The shipment has been created.');
            $labelCreatedMessage   =$this->__('The shipping label has been created.');
            
            $this->_getSession()->addSuccess($isNeedCreateLabel ? $shipmentCreatedMessage . ' ' . $labelCreatedMessage : $shipmentCreatedMessage);
            Mage::getSingleton('adminhtml/session')->getCommentText(true);
        }
        catch(Mage_Core_Exception $e) {
            if($isNeedCreateLabel) {
                $responseAjax->setError(true);
                $responseAjax->setMessage($e->getMessage());
            } else {
                $this->_getSession()->addError($e->getMessage());
                $this->_redirect('*/*/new', array(
                    'order_id'=>$this->getRequest()->getParam('order_id')
                ));
            }
        }
        catch(Exception $e) {
            Mage::logException($e);
            if($isNeedCreateLabel) {
                $responseAjax->setError(true);
                $responseAjax->setMessage(Mage::helper('sales')->__('An error occurred while creating shipping label.'));
            } else {
                $this->_getSession()->addError($this->__('Cannot save shipment.'));
                $this->_redirect('*/*/new', array(
                    'order_id'=>$this->getRequest()->getParam('order_id')
                ));
            }
        
        }
        if($isNeedCreateLabel) {
            $this->getResponse()->setBody($responseAjax->toJson());
        } else {
            $this->_redirect('*/sales_order/view', array(
                'order_id'=>$shipment->getOrderId()
            ));
        }
    }
    
    
}
