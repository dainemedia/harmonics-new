<?php
//Version 105
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2013 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Catalog product attribute controller
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
include_once("Mage/Adminhtml/controllers/Catalog/Product/AttributeController.php");
class Microbiz_Connector_Catalog_Product_AttributeController extends Mage_Adminhtml_Catalog_Product_AttributeController
{


    /*protected function _construct() {
        //Mage::getSingleton('core/session', array('name'=>'adminhtml'));
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
     *  Saving Attribute information
     *	Extending from Magento Core controller Mage_Adminhtml_Catalog_Product_AttributeController for triggering Custom save event
     *
     */

    public function saveAction()
    {
        $data = $this->getRequest()->getPost();

        if ($data) {
            /** @var $session Mage_Admin_Model_Session */
            $session = Mage::getSingleton('adminhtml/session');

            $redirectBack   = $this->getRequest()->getParam('back', false);
            /* @var $model Mage_Catalog_Model_Entity_Attribute */
            $model = Mage::getModel('catalog/resource_eav_attribute');
            /* @var $helper Mage_Catalog_Helper_Product */
            $helper = Mage::helper('catalog/product');

            $id = $this->getRequest()->getParam('attribute_id');

            //validate attribute_code
            if (isset($data['attribute_code'])) {
                $validatorAttrCode = new Zend_Validate_Regex(array('pattern' => '/^[a-z][a-z_0-9]{1,254}$/'));
                if (!$validatorAttrCode->isValid($data['attribute_code'])) {
                    $session->addError(
                        Mage::helper('catalog')->__('Attribute code is invalid. Please use only letters (a-z), numbers (0-9) or underscore(_) in this field, first character should be a letter.')
                    );
                    $this->_redirect('*/*/edit', array('attribute_id' => $id, '_current' => true));
                    return;
                }
            }


            //validate frontend_input
            if (isset($data['frontend_input'])) {
                /** @var $validatorInputType Mage_Eav_Model_Adminhtml_System_Config_Source_Inputtype_Validator */
                $validatorInputType = Mage::getModel('eav/adminhtml_system_config_source_inputtype_validator');
                if (!$validatorInputType->isValid($data['frontend_input'])) {
                    foreach ($validatorInputType->getMessages() as $message) {
                        $session->addError($message);
                    }
                    $this->_redirect('*/*/edit', array('attribute_id' => $id, '_current' => true));
                    return;
                }
            }

            if ($id) {
                $model->load($id);

                if (!$model->getId()) {
                    $session->addError(
                        Mage::helper('catalog')->__('This Attribute no longer exists'));
                    $this->_redirect('*/*/');
                    return;
                }

                // entity type check
                if ($model->getEntityTypeId() != $this->_entityTypeId) {
                    $session->addError(
                        Mage::helper('catalog')->__('This attribute cannot be updated.'));
                    $session->setAttributeData($data);
                    $this->_redirect('*/*/');
                    return;
                }

                $data['attribute_code'] = $model->getAttributeCode();
                $data['is_user_defined'] = $model->getIsUserDefined();
                $data['frontend_input'] = $model->getFrontendInput();
            } else {
                /**
                 * @todo add to helper and specify all relations for properties
                 */
                $data['source_model'] = $helper->getAttributeSourceModelByInputType($data['frontend_input']);
                $data['backend_model'] = $helper->getAttributeBackendModelByInputType($data['frontend_input']);
            }

            if (!isset($data['is_configurable'])) {
                $data['is_configurable'] = 0;
            }
            if (!isset($data['is_filterable'])) {
                $data['is_filterable'] = 0;
            }
            if (!isset($data['is_filterable_in_search'])) {
                $data['is_filterable_in_search'] = 0;
            }

            if (is_null($model->getIsUserDefined()) || $model->getIsUserDefined() != 0) {
                $data['backend_type'] = $model->getBackendTypeByInput($data['frontend_input']);
            }

            $defaultValueField = $model->getDefaultValueByInput($data['frontend_input']);
            if ($defaultValueField) {
                $data['default_value'] = $this->getRequest()->getParam($defaultValueField);
            }

            if(!isset($data['apply_to'])) {
                $data['apply_to'] = array();
            }
            if(!$id && !$data['is_used_to_create_mapping']) {
                $data['is_mappable'] = 1;
            }
            else if(!$id && $data['is_used_to_create_mapping']) {
                $data['is_mappable'] = 0;
            }
            else if($id && !$data['is_used_to_create_mapping']) {
                $data['is_mappable'] = 1;
            }
            else if($id && $data['is_used_to_create_mapping']) {
                $data['is_mappable'] = 0;
            }

            //filter
            $data = $this->_filterPostData($data);
            $model->addData($data);

            if (!$id) {
                $model->setEntityTypeId($this->_entityTypeId);
                $model->setIsUserDefined(1);
            }


            if ($this->getRequest()->getParam('set') && $this->getRequest()->getParam('group')) {
                // For creating product attribute on product page we need specify attribute set and group
                $model->setAttributeSetId($this->getRequest()->getParam('set'));
                $model->setAttributeGroupId($this->getRequest()->getParam('group'));
            }

            try {
                $model->save();
                if($id && $data['is_used_to_create_mapping']) {
                    $attributeRelationCollection = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('magento_id',$id);
                    if($attributeRelationCollection) {
                        $attributeRelationOptionsData = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('magento_id',$id)->getFirstItem()->getData();
                        $attributeRelationCollection->walk('delete');
                        Mage::helper('microbiz_connector')->deleteAttributeMappings($id);
                    }
                    $attributeOptionsRelationData = Mage::getModel('mbizattributeoption/mbizattributeoption')->getCollection()->addFieldToFilter('mbiz_attr_id', $attributeRelationOptionsData['mbiz_id']);
                    if ($attributeOptionsRelationData) {
                        $attributeOptionsRelationData->walk('delete');
                    }
                }
                $isSyncCreate = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('magento_id', $model->getAttributeId())->setOrder('id', 'asc')->getData();

                if($isSyncCreate){
                    Mage::dispatchEvent(
                        'ktree_attribute_save',
                        array('attribute' => $model)
                    );
                }
                $session->addSuccess(
                    Mage::helper('catalog')->__('The product attribute has been saved.'));

                /**
                 * Clear translation cache because attribute labels are stored in translation
                 */
                Mage::app()->cleanCache(array(Mage_Core_Model_Translate::CACHE_TAG));
                $session->setAttributeData(false);
                if ($this->getRequest()->getParam('popup')) {
                    $this->_redirect('adminhtml/catalog_product/addAttribute', array(
                        'id'       => $this->getRequest()->getParam('product'),
                        'attribute'=> $model->getId(),
                        '_current' => true
                    ));
                } elseif ($redirectBack) {
                    $this->_redirect('*/*/edit', array('attribute_id' => $model->getId(),'_current'=>true));
                } else {
                    $this->_redirect('*/*/', array());
                }
                return;
            } catch (Exception $e) {
                $session->addError($e->getMessage());
                $session->setAttributeData($data);
                $this->_redirect('*/*/edit', array('attribute_id' => $id, '_current' => true));
                return;
            }
        }
        $this->_redirect('*/*/');
    }

    public function deleteAction()
    {
        if ($id = $this->getRequest()->getParam('attribute_id')) {
            $model = Mage::getModel('catalog/resource_eav_attribute');

            // entity type check
            $model->load($id);
            $attributeId = $id;
            if ($model->getEntityTypeId() != $this->_entityTypeId) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('catalog')->__('This attribute cannot be deleted.'));
                $this->_redirect('*/*/');
                return;
            }

            try {


                $model->delete();
                $attributeRelationCollection = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('magento_id',$attributeId);
                if($attributeRelationCollection) {
                    $attributeRelationOptionsData = Mage::getModel('mbizattribute/mbizattribute')->getCollection()->addFieldToFilter('magento_id',$attributeId)->getFirstItem()->getData();

                    $attributeRelationCollection->walk('delete');
                    $attributeOptionsRelationData = Mage::getModel('mbizattributeoption/mbizattributeoption')->getCollection()->addFieldToFilter('mbiz_attr_id', $attributeRelationOptionsData['mbiz_id']);
                    if ($attributeOptionsRelationData) {
                        $attributeOptionsRelationData->walk('delete');
                    }
                    Mage::helper('microbiz_connector')->deleteAttributeMappings($attributeId);
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('catalog')->__('The product attribute has been deleted.'));
                $this->_redirect('*/*/');
                return;
            }
            catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('attribute_id' => $this->getRequest()->getParam('attribute_id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('catalog')->__('Unable to find an attribute to delete.'));
        $this->_redirect('*/*/');
    }


}
