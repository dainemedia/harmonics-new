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
 * @package     Mage_Catalog
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Catalog category api
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */

class  Microbiz_Connector_Model_Category_Api extends Mage_Catalog_Model_Category_Api
{
    public function __construct()
    {
        $this->_storeIdSessionField = 'category_store_id';
    }

    /**
     * Retrieve category tree
     *
     * @param int $parent
     * @param string|int $store
     * @return array
     */
    public function tree($parentId = null, $store = null)
    {
        if (is_null($parentId) && !is_null($store)) {
            $parentId = Mage::app()->getStore($this->_getStoreId($store))->getRootCategoryId();
        } elseif (is_null($parentId)) {
            $parentId = 1;
        }

        /* @var $tree Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Tree */
        $tree = Mage::getResourceSingleton('catalog/category_tree')
            ->load();

        $root = $tree->getNodeById($parentId);

        if($root && $root->getId() == 1) {
            $root->setName(Mage::helper('catalog')->__('Root'));
        }

        $collection = Mage::getModel('catalog/category')->getCollection()
            ->setStoreId($this->_getStoreId($store))
            ->addAttributeToSelect('*');
        // ->addAttributeToSelect('is_active');

        $tree->addCollectionData($collection, true);

        return $this->_nodeToArray($root);
    }

    /**
     * Convert node to array
     *
     * @param Varien_Data_Tree_Node $node
     * @return array
     */
    protected function _nodeToArray(Varien_Data_Tree_Node $node)
    {
        // Only basic category data
        $result = array();
        /* $result['category_id'] = $node->getId();
        $result['parent_id']   = $node->getParentId();
        $result['name']        = $node->getName();
        $result['is_active']   = $node->getIsActive();
        $result['position']    = $node->getPosition();
        $result['level']       = $node->getLevel(); */
        $result = $node->getData();
        $result['children']    = array();

        foreach ($node->getChildren() as $child) {
            $result['children'][] = $this->_nodeToArray($child);
        }

        return $result;
    }

    public function createCategory($parentId,$customerData)
    {
        //Mage::log("came to createcategory");
        //Mage::log($parentId);
        //::log($customerData);
        $category = new Mage_Catalog_Model_Category();
        $category->setName($customerData['name']);
        if($customerData['description'])
        {
            $category->setDescription($customerData['description']);
        }

        $category->setDisplayMode('PRODUCTS');
        $category->setIsActive($customerData['is_active']);

        if($customerData['parent_id']==1)
        {
            $category->setSyncCatCreate(1);
        }

        $parentCategory = Mage::getModel('catalog/category')->load($parentId);
        $category->setPath($parentCategory->getPath());
        $category->save();
        $categoryId = $category->getId();
        //Mage::log("category id is");
        //Mage::log($categoryId);
        unset($category);

        //Mage::log($category);
        //Mage::log($category->getId());
        return $categoryId;
    }

    /**
     * Update category data
     *
     * @param int $categoryId
     * @param array $categoryData
     * @param string|int $store
     * @return boolean
     */
    public function update($categoryId, $categoryData, $store = null)
    {
        $category = $this->_initCategory($categoryId, $store);

        foreach ($category->getAttributes() as $attribute) {
            if ($this->_isAllowedAttribute($attribute)
                && isset($categoryData[$attribute->getAttributeCode()])) {
                $category->setData(
                    $attribute->getAttributeCode(),
                    $categoryData[$attribute->getAttributeCode()]
                );
            }
        }

        try {
            (isset($categoryData['use_post_data_config'])) ? $category->setData('use_post_data_config', $categoryData['use_post_data_config']) : null;
            $validate = $category->validate();
            if ($validate !== true) {
                foreach ($validate as $code => $error) {
                    if ($error === true) {
                        Mage::throwException(Mage::helper('catalog')->__('Attribute "%s" is required.', $code));
                    }
                    else {
                        Mage::throwException($error);
                    }
                }
            }

            $category->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        } catch (Mage_Eav_Model_Entity_Attribute_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }
        catch (Zend_Db_Statement_Exception $e) {
            throw new Zend_Db_Statement_Exception($e->getMessage(), (int) $e->getCode(), $e);
        }
        catch (Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }

        return true;
    }

} // Class Mage_Catalog_Model_Category_Api End
