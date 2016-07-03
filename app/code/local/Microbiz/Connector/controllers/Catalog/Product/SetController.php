<?php
//Version 101
/**
 * Overriding Adminhtml Product Set controller
 *
 * @category   Mage
 * @package    microbiz_Connector
 * @author      KT097
 */
include_once("Mage/Adminhtml/controllers/Catalog/Product/SetController.php");
class Microbiz_Connector_Catalog_Product_SetController extends Mage_Adminhtml_Catalog_Product_SetController
{
    protected function _construct() {
        Mage::getSingleton('core/session', array('name'=>'adminhtml'));
        if (!Mage::getSingleton('admin/session')->isLoggedIn()) {
            header('Location: '.Mage::helper('adminhtml')->getUrl('adminhtml/index/login'));
            exit;
            $this->_forward('adminhtml/index/login');
            return;
        } else {
            parent::_construct();
        }
    }
    public function deleteAction()
    {
        $setId = $this->getRequest()->getParam('id');

        try {
            //Load product model collecttion filtered by attribute set id
            $products = Mage::getModel('catalog/product')
                ->getCollection()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('attribute_set_id', $setId);

            //process your product collection as per your bussiness logic
            $productsIds = array();
            foreach($products as $p){
                $productinfo=$p->getData();
                $productsIds[] = $productinfo['entity_id'];
            }
            Mage::getModel('eav/entity_attribute_set')->setId($setId)->delete();

            $this->_getSession()->addSuccess($this->__('The attribute set has been removed.'));
            Mage::dispatchEvent(
                'catalog_product_attributeset_delete',
                array('set_id'=>$setId, 'allProductIds'=>$productsIds)
            );
            // for removing Product relations with Mbiz for this attributeset
            foreach($productsIds as $productsId) {
                Mage::helper('microbiz_connector')->deleteAppRelation($productsId,'Product');
            }
            Mage::helper('microbiz_connector')->deleteAppRelation($setId,'AttributeSets');
            $this->getResponse()->setRedirect($this->getUrl('*/*/'));
        } catch (Exception $e) {
            $this->_getSession()->addError($this->__('An error occurred while deleting this set.'));
            $this->_redirectReferer();
        }
    }
    /**
     * Save attribute set action
     *
     * [POST] Create attribute set from another set and redirect to edit page
     * [AJAX] Save attribute set data
     *
     */
    public function saveAction()
    {
        $entityTypeId   = $this->_getEntityTypeId();
        $hasError       = false;
        $attributeSetId = $this->getRequest()->getParam('id', false);
        $isNewSet       = $this->getRequest()->getParam('gotoEdit', false) == '1';
//print_r($this->getRequest()->getPost()); exit;
        /* @var $model Mage_Eav_Model_Entity_Attribute_Set */
        $model  = Mage::getModel('eav/entity_attribute_set')
            ->setEntityTypeId($entityTypeId);

        /** @var $helper Mage_Adminhtml_Helper_Data */
        $helper = Mage::helper('adminhtml');
        $isSyncCreate = $this->getRequest()->getParam('sync_attr_set_create');
        try {
            if ($isNewSet) {
                //filter html tags
                $name = $helper->stripTags($this->getRequest()->getParam('attribute_set_name'));
                $model->setAttributeSetName(trim($name));
                $model->setSyncAttrSetCreate($this->getRequest()->getParam('sync_attr_set_create'));
            } else {
                if ($attributeSetId) {
                    $model->load($attributeSetId);
                }
                if (!$model->getId()) {
                    Mage::throwException(Mage::helper('catalog')->__('This attribute set no longer exists.'));
                }
                $data = Mage::helper('core')->jsonDecode($this->getRequest()->getPost('data'));

                //filter html tags
                $data['attribute_set_name'] = $helper->stripTags($data['attribute_set_name']);
                $name = $helper->stripTags($data['attribute_set_name']);

                $model->organizeData($data);
                $isSyncCreate = $data['sync_attr_set_create'];
                $model->setSyncAttrSetCreate($data['sync_attr_set_create']);
            }

            $model->validate();
            if ($isNewSet) {

                $model->save();
                $model->initFromSkeleton($this->getRequest()->getParam('skeleton_set'));
            }

            $model->save();
            $this->_getSession()->addSuccess(Mage::helper('catalog')->__('The attribute set has been saved.'));
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $hasError = true;
        } catch (Exception $e) {
            $this->_getSession()->addException($e,
                Mage::helper('catalog')->__('An error occurred while saving the attribute set.'));
            $hasError = true;
        }

        if($isSyncCreate){
            Mage::dispatchEvent(
                'ktree_attributeset_save',
                array('attribute_set_id' => $model->getId(),'attribute_set_name' => $name)
            );
        }

        if ($isNewSet) {

            if ($hasError) {
                $this->_redirect('*/*/add');
            } else {
                $this->_redirect('*/*/edit', array('id' => $model->getId()));
            }
        } else {

            $removeGroup=array();
            $postdata1=array();
            $postdata=$this->getRequest()->getPost();
            $postdata1=json_decode($postdata['data']);
            $removeGroup=$postdata1->removeGroups;
            //Mage::log($removeGroup);
            if(count($removeGroup)){
                Mage::dispatchEvent(
                    'catalog_product_attributegroup_delete',
                    array()
                );
            }
            $response = array();
            if ($hasError) {
                $this->_initLayoutMessages('adminhtml/session');
                $response['error']   = 1;
                $response['message'] = $this->getLayout()->getMessagesBlock()->getGroupedHtml();
            } else {
                $response['error']   = 0;
                $response['url']     = $this->getUrl('*/*/');
            }
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
        }
    }

}