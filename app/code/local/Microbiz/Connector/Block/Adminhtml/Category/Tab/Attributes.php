<?php
//version 102
/**
 * Created by PhpStorm.
 * User: ktree
 * Date: 8/4/14
 * Time: 7:34 PM
 */
class Microbiz_Connector_Block_Adminhtml_Category_Tab_Attributes extends Mage_Adminhtml_Block_Catalog_Category_Tab_Attributes
{


    /**
     * Prepare form before rendering HTML
     *
     * @return Mage_Adminhtml_Block_Catalog_Category_Tab_Attributes
     */
    protected function _prepareForm() {

        parent::_prepareForm();
        $form = $this->getForm();
        $rootId = Mage_Catalog_Model_Category::TREE_ROOT_ID;

        foreach($this->getForm()->getElements() as $fieldset){
            if($this->getCategory()->getLevel() > 1 || (!($this->getCategory()->getId()) && $this->getCategory()->getLevel() >=0 && $this->getRequest()->getParam('parent', $rootId)>1))
            {
             $fieldset->removeField('sync_cat_create');
            }
        }

        return $this;
    }


}