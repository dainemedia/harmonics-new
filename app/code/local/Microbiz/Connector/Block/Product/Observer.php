<?php
//Version 103
/**
 * Observer for core events handling
 *
 */

class Microbiz_Connector_Block_Product_Observer
{
     /**
     * Observes event 'adminhtml_catalog_product_edit_prepare_form'
     * and adds custom format for date input
     *
     * @param Varien_Event_Observer $observer
     * @return void
     */

    public function __construct()
    {
    }
       
    public function onPrepare($observer)
    {
       $product = Mage::registry('product');
       
	$form = $observer->getEvent()->getForm();

	if ($product->getId()) {
            Mage::log('came to block observer',null,'importprod.log');

            if($product->getTypeId()=='mbizgiftcard') {
                $productModel = Mage::getModel('catalog/product')->load($product->getId());
                $productModel->setSyncStatus(0)->setId($product->getId())->save();
                foreach($form->getElements() as $fieldset){
                    $fieldset->removeField('sync_status');
                    $fieldset->removeField('pos_product_status');

                }
            }
            else {
	    $updatemsg = $product->getSyncUpdateMsg();
	    $pos_product_status = $form->getElement('pos_product_status');
	    if($pos_product_status)
        {
            $pos_product_status->setAfterElementHtml("<div>{$updatemsg}</div>");
        }

	if($product->getSyncPrdCreate()){
           $sync_prd_create = $form->getElement('sync_prd_create');
           if($sync_prd_create)
           {
              // $sync_prd_create->setDisabled('disabled');
           }
	    }
        else{
		$sync_prd_create = $form->getElement('sync_prd_create');
		$sync_status = $form->getElement('sync_status');
            /*if ($sync_prd_create && $sync_status) {
            $sync_status->setDisabled('disabled');
                   $_disableSyncStatus = '';
                   $sync_prd_create->setAfterElementHtml(
                       '<div>Select \'Yes\' To Create Product in MicroBiz</div>');
               $sync_status->setAfterElementHtml('<script type="text/javascript">'
                       ."
                       $('sync_prd_create').disableSyncStatus = function() {
                   $('sync_status').value = $('sync_prd_create').value;".
                           $_disableSyncStatus
                       ."}.bind($('sync_prd_create'));
                       Event.observe('sync_prd_create', 'change', $('sync_prd_create').disableSyncStatus);
                       $('sync_prd_create').disableSyncStatus();
                       "
                       . '</script>'
                   ); }*/
		}
            }

	}else{
            Mage::log("came to prod create new ",null,'importprod.log');
            Mage::log($product->getTypeId(),null,'importprod.log');

            if($product->getTypeId()=='mbizgiftcard') {
                foreach($form->getElements() as $fieldset){
                    $fieldset->removeField('sync_status');
                    $fieldset->removeField('pos_product_status');

                }
            }


		$sync_prd_create = $form->getElement('sync_prd_create');
		$sync_status = $form->getElement('sync_status');

        /*if ($sync_prd_create && $sync_status) {
        $sync_status->setDisabled('disabled');
               $_disableSyncStatus = '';
           $sync_prd_create->setAfterElementHtml(
                   '<div>Select \'Yes\' To Create Product in MicroBiz</div>');
            $sync_status->setAfterElementHtml('<script type="text/javascript">'
                   ."
                       $('sync_prd_create').disableSyncStatus = function() {
               $('sync_status').value = $('sync_prd_create').value;".
                       $_disableSyncStatus
                   ."}.bind($('sync_prd_create'));
                   Event.observe('sync_prd_create', 'change', $('sync_prd_create').disableSyncStatus);
                   $('sync_prd_create').disableSyncStatus();
                   "
                   . '</script>'
               ); }*/

	}
	return $this;
    }
}
