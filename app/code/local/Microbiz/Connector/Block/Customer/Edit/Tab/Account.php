<?php
//Version 101
class Microbiz_Connector_Block_Customer_Edit_Tab_Account extends Mage_Adminhtml_Block_Customer_Edit_Tab_Account
{
 public function __construct()
    {
        parent::__construct();
    }

    public function initForm()
    {
	$form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('_account');
        $form->setFieldNameSuffix('account');

        $customer = Mage::registry('current_customer');

        /* @var $customerForm Mage_Customer_Model_Form */
        $customerForm = Mage::getModel('customer/form');
        $customerForm->setEntity($customer)
            ->setFormCode('adminhtml_customer')
            ->initDefaultValues();

        $fieldset = $form->addFieldset('base_fieldset',
            array('legend'=>Mage::helper('customer')->__('Account Information'))
        );

        $attributes = $customerForm->getAttributes();
        foreach ($attributes as $attribute) {
            $attribute->unsIsVisible();
        }
        $this->_setFieldset($attributes, $fieldset);

        if ($customer->getId()) {
            $form->getElement('website_id')->setDisabled('disabled');
            $form->getElement('created_in')->setDisabled('disabled');
	    $fieldset->removeField('sync_update_msg');
	    $sync_update_msg = $customer->getSyncUpdateMsg();
 
	    $pos_cus_status = $form->getElement('pos_cus_status');
	    $pos_cus_status->setAfterElementHtml("<div>{$sync_update_msg}</div>");


            /*if($customer->getSyncCusCreate())
            $form->getElement('sync_cus_create')->setDisabled('disabled');
	    else{
		$sync_cus_create = $form->getElement('sync_cus_create');
		$sync_status = $form->getElement('sync_status');
		
        	$prefix = $form->getHtmlIdPrefix();

	 if ($sync_cus_create && $sync_status) {
		$sync_status->setDisabled('disabled');
            $_disableSyncStatus = '';
            $sync_cus_create->setAfterElementHtml(
                '<div>Select \'Yes\' To Create Customer in MicroBiz</div>');
	    $sync_status->setAfterElementHtml('<script type="text/javascript">'
                ." 
                $('{$prefix}sync_cus_create').disableSyncStatus = function() {	     
		    $('{$prefix}sync_status').value = $('{$prefix}sync_cus_create').value;".
                    $_disableSyncStatus
                ."}.bind($('{$prefix}sync_cus_create'));
                Event.observe('{$prefix}sync_cus_create', 'change', $('{$prefix}sync_cus_create').disableSyncStatus);
                $('{$prefix}sync_cus_create').disableSyncStatus();
                "
                . '</script>'
            ); }
		}*/

	    //$customer->getSynccustomer();
        } else {
            $fieldset->removeField('created_in');
	    $fieldset->removeField('sync_update_msg');
		//$sync_cus_create = $form->getElement('sync_cus_create');
		$sync_status = $form->getElement('sync_status');
        	$prefix = $form->getHtmlIdPrefix();
	 /*if ($sync_status) {
		$sync_status->setDisabled('disabled');
            $_disableSyncStatus = '';
	     // $sync_cus_create->setAfterElementHtml('<script type="text/javascript">'."alert('HI')".'</script>');
             $sync_cus_create->setAfterElementHtml(
                '<div>Select \'Yes\' To Create Customer in MicroBiz</div>');

	     $sync_status->setAfterElementHtml('<script type="text/javascript">'
                .   "  
                    $('{$prefix}sync_cus_create').disableSyncStatus = function() {
		    $('{$prefix}sync_status').value = $('{$prefix}sync_cus_create').value;
		    ".
                    $_disableSyncStatus
                ."}.bind($('{$prefix}sync_cus_create'));
                Event.observe('{$prefix}sync_cus_create', 'change', $('{$prefix}sync_cus_create').disableSyncStatus);
                $('{$prefix}sync_cus_create').disableSyncStatus();
                "
                . '</script>'
            ); }*/
	   
           }

//        if (Mage::app()->isSingleStoreMode()) {
//            $fieldset->removeField('website_id');
//            $fieldset->addField('website_id', 'hidden', array(
//                'name'      => 'website_id'
//            ));
//            $customer->setWebsiteId(Mage::app()->getStore(true)->getWebsiteId());
//        }

        
        if ($customer->getId()) {
            if (!$customer->isReadonly()) {
                // add password management fieldset
                $newFieldset = $form->addFieldset(
                    'password_fieldset',
                    array('legend'=>Mage::helper('customer')->__('Password Management'))
                );
                // New customer password
                $field = $newFieldset->addField('new_password', 'text',
                    array(
                        'label' => Mage::helper('customer')->__('New Password'),
                        'name'  => 'new_password',
                        'class' => 'validate-new-password'
                    )
                );
                $field->setRenderer($this->getLayout()->createBlock('adminhtml/customer_edit_renderer_newpass'));

                // prepare customer confirmation control (only for existing customers)
                $confirmationKey = $customer->getConfirmation();
                if ($confirmationKey || $customer->isConfirmationRequired()) {
                    $confirmationAttribute = $customer->getAttribute('confirmation');
                    if (!$confirmationKey) {
                        $confirmationKey = $customer->getRandomConfirmationKey();
                    }
                    $element = $fieldset->addField('confirmation', 'select', array(
                        'name'  => 'confirmation',
                        'label' => Mage::helper('customer')->__($confirmationAttribute->getFrontendLabel()),
                    ))->setEntityAttribute($confirmationAttribute)
                        ->setValues(array('' => 'Confirmed', $confirmationKey => 'Not confirmed'));

                    // prepare send welcome email checkbox, if customer is not confirmed
                    // no need to add it, if website id is empty
                    if ($customer->getConfirmation() && $customer->getWebsiteId()) {
                        $fieldset->addField('sendemail', 'checkbox', array(
                            'name'  => 'sendemail',
                            'label' => Mage::helper('customer')->__('Send Welcome Email after Confirmation')
                        ));
                        $customer->setData('sendemail', '1');
                    }
                }
            }
        } else {
            $newFieldset = $form->addFieldset(
                'password_fieldset',
                array('legend'=>Mage::helper('customer')->__('Password Management'))
            );
            $field = $newFieldset->addField('password', 'text',
                array(
                    'label' => Mage::helper('customer')->__('Password'),
                    'class' => 'input-text required-entry validate-password',
                    'name'  => 'password',
                    'required' => true
                )
            );
            $field->setRenderer($this->getLayout()->createBlock('adminhtml/customer_edit_renderer_newpass'));

            // prepare send welcome email checkbox
            $fieldset->addField('sendemail', 'checkbox', array(
                'label' => Mage::helper('customer')->__('Send Welcome Email'),
                'name'  => 'sendemail',
                'id'    => 'sendemail',
            ));
            $customer->setData('sendemail', '1');
            if (!Mage::app()->isSingleStoreMode()) {
                $fieldset->addField('sendemail_store_id', 'select', array(
                    'label' => $this->helper('customer')->__('Send From'),
                    'name' => 'sendemail_store_id',
                    'values' => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm()
                ));
            }
        }

        // make sendemail and sendmail_store_id disabled, if website_id has empty value
        $isSingleMode = Mage::app()->isSingleStoreMode();
        $sendEmailId = $isSingleMode ? 'sendemail' : 'sendemail_store_id';
        $sendEmail = $form->getElement($sendEmailId);

        $prefix = $form->getHtmlIdPrefix();
        if ($sendEmail) {
            $_disableStoreField = '';
            if (!$isSingleMode) {
                $_disableStoreField = "$('{$prefix}sendemail_store_id').disabled=(''==this.value || '0'==this.value);";
            }
            $sendEmail->setAfterElementHtml(
                '<script type="text/javascript">'
                . "
                $('{$prefix}website_id').disableSendemail = function() {
                    $('{$prefix}sendemail').disabled = ('' == this.value || '0' == this.value);".
                    $_disableStoreField
                ."}.bind($('{$prefix}website_id'));
                Event.observe('{$prefix}website_id', 'change', $('{$prefix}website_id').disableSendemail);
                $('{$prefix}website_id').disableSendemail();
                "
                . '</script>'
            );
        }

        if ($customer->isReadonly()) {
            foreach ($customer->getAttributes() as $attribute) {
                $element = $form->getElement($attribute->getAttributeCode());
                if ($element) {
                    $element->setReadonly(true, true);
                }
            }
        }

        $form->setValues($customer->getData());
        $this->setForm($form);
        return $this;
    }

    /**
     * Return predefined additional element types
     *
     * @return array
     */
    protected function _getAdditionalElementTypes()
    {
        return array(
            'file'      => Mage::getConfig()->getBlockClassName('adminhtml/customer_form_element_file'),
            'image'     => Mage::getConfig()->getBlockClassName('adminhtml/customer_form_element_image'),
            'boolean'   => Mage::getConfig()->getBlockClassName('adminhtml/customer_form_element_boolean'),
        );
    }
}
