<?php
// Version 102
/**
 * Extended customercontroller of Customer admin controller
 *
 * @category    Ktree
 * @package     Ktree_ExtendedMbizConnector
 * @author      KT097
 */
 include_once("Mage/Adminhtml/controllers/CustomerController.php");
class Microbiz_Connector_CustomerController extends Mage_Adminhtml_CustomerController
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
    /**
     * Save customer action
	 * after customer save it will dispatch an custom event ktree_customer_save
	 * we can get the array of Posted values of customer data
     */
    public function saveAction()
    {
        $data = $this->getRequest()->getPost();
		if ($data) {
			/*if (isset($data['account']['sync_cus_create'])) {
                $data['account']['sync_status']=$data['account']['sync_cus_create'];
            }*/

            $redirectBack = $this->getRequest()->getParam('back', false);
            $this->_initCustomer('customer_id');

            /** @var $customer Mage_Customer_Model_Customer */
            $customer = Mage::registry('current_customer');
			$customer_test=Mage::registry('current_customer');
			$i=0;
			$customer_data=$customer_test->toArray();
			$customer_data['addresses'][]=array();
			foreach($customer_test->getAddresses() as $address ) {
			$customer_data['addresses'][$i]=$address->getData();
				$i++;
			}
			/** @var $customerForm Mage_Customer_Model_Form */
            $customerForm = Mage::getModel('customer/form');
            $customerForm->setEntity($customer)
                ->setFormCode('adminhtml_customer')
                ->ignoreInvisible(false)
            ;

            $formData = $customerForm->extractData($this->getRequest(), 'account');

            // Handle 'disable auto_group_change' attribute
            if (isset($formData['disable_auto_group_change'])) {
                $formData['disable_auto_group_change'] = empty($formData['disable_auto_group_change']) ? '0' : '1';
            }
			/*if (isset($formData['sync_cus_create'])) {
                $formData['sync_status']=$formData['sync_cus_create'];
            }*/

            $errors = $customerForm->validateData($formData);
            if ($errors !== true) {
                foreach ($errors as $error) {
                    $this->_getSession()->addError($error);
                }
                $this->_getSession()->setCustomerData($data);
                $this->getResponse()->setRedirect($this->getUrl('*/customer/edit', array('id' => $customer->getId())));
                return;
            }

            $customerForm->compactData($formData);

            // Unset template data
            if (isset($data['address']['_template_'])) {
                unset($data['address']['_template_']);
            }

            $modifiedAddresses = array();
            if (!empty($data['address'])) {
                /** @var $addressForm Mage_Customer_Model_Form */
                $addressForm = Mage::getModel('customer/form');
                $addressForm->setFormCode('adminhtml_customer_address')->ignoreInvisible(false);

                foreach (array_keys($data['address']) as $index) {
                    $address = $customer->getAddressItemById($index);
                    if (!$address) {
                        $address = Mage::getModel('customer/address');
                    }

                    $requestScope = sprintf('address/%s', $index);
                    $formData = $addressForm->setEntity($address)
                        ->extractData($this->getRequest(), $requestScope);

                    // Set default billing and shipping flags to address
                    $isDefaultBilling = isset($data['account']['default_billing'])
                        && $data['account']['default_billing'] == $index;
                    $address->setIsDefaultBilling($isDefaultBilling);
                    $isDefaultShipping = isset($data['account']['default_shipping'])
                        && $data['account']['default_shipping'] == $index;
                    $address->setIsDefaultShipping($isDefaultShipping);

                    $errors = $addressForm->validateData($formData);
                    if ($errors !== true) {
                        foreach ($errors as $error) {
                            $this->_getSession()->addError($error);
                        }
                        $this->_getSession()->setCustomerData($data);
                        $this->getResponse()->setRedirect($this->getUrl('*/customer/edit', array(
                            'id' => $customer->getId())
                        ));
                        return;
                    }

                    $addressForm->compactData($formData);

                    // Set post_index for detect default billing and shipping addresses
                    $address->setPostIndex($index);

                    if ($address->getId()) {
                        $modifiedAddresses[] = $address->getId();
                    } else {
                        $customer->addAddress($address);
                    }
                }
            }

            // Default billing and shipping
            if (isset($data['account']['default_billing'])) {
                $customer->setData('default_billing', $data['account']['default_billing']);
            }
            if (isset($data['account']['default_shipping'])) {
                $customer->setData('default_shipping', $data['account']['default_shipping']);
            }
            if (isset($data['account']['confirmation'])) {
                $customer->setData('confirmation', $data['account']['confirmation']);
            }
			if (isset($data['account']['sync_status'])) {
                $customer->setData('sync_status', $data['account']['sync_status']);
            }

            // Mark not modified customer addresses for delete
            foreach ($customer->getAddressesCollection() as $customerAddress) {
                if ($customerAddress->getId() && !in_array($customerAddress->getId(), $modifiedAddresses)) {
                    $customerAddress->setData('_deleted', true);
                }
            }

            if (Mage::getSingleton('admin/session')->isAllowed('customer/newsletter')) {
                $customer->setIsSubscribed(isset($data['subscription']));
            }

            if (isset($data['account']['sendemail_store_id'])) {
                $customer->setSendemailStoreId($data['account']['sendemail_store_id']);
            }

            $isNewCustomer = $customer->isObjectNew();
            try {
                $sendPassToEmail = false;
                // Force new customer confirmation
                if ($isNewCustomer) {
                    $customer->setPassword($data['account']['password']);
                    $customer->setForceConfirmed(true);
                    if ($customer->getPassword() == 'auto') {
                        $sendPassToEmail = true;
                        $customer->setPassword($customer->generatePassword());
                    }
                }

                Mage::dispatchEvent('adminhtml_customer_prepare_save', array(
                    'customer'  => $customer,
                    'request'   => $this->getRequest()
                ));

                $customer->save();
				$origcustomerdata=$customer->getData();
				//$isSync=$origcustomerdata['sync_cus_create'];
				$syncStatus=$origcustomerdata['sync_status'];
				$overallsyncStatus=Mage::getStoreConfig('connector/settings/syncstatus');
				//if customer sync is enable then dispatch the ktree customer save event
				if($overallsyncStatus) {
					if($syncStatus) {
						Mage::dispatchEvent('ktree_customer_save', array(
							'customer'  => $customer,
							'request'   => $this->getRequest(),
							'olddata'   => $customer_data,
							'postdata'   => $data,
						));
					}
				}
                // Send welcome email
                if ($customer->getWebsiteId() && (isset($data['account']['sendemail']) || $sendPassToEmail)) {
                    $storeId = $customer->getSendemailStoreId();
                    if ($isNewCustomer) {
                        $customer->sendNewAccountEmail('registered', '', $storeId);
                    } elseif ((!$customer->getConfirmation())) {
                        // Confirm not confirmed customer
                        $customer->sendNewAccountEmail('confirmed', '', $storeId);
                    }
                }

                if (!empty($data['account']['new_password'])) {
                    $newPassword = $data['account']['new_password'];
                    if ($newPassword == 'auto') {
                        $newPassword = $customer->generatePassword();
                    }
                    $customer->changePassword($newPassword);
                    $customer->sendPasswordReminderEmail();
                }

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('The customer has been saved.')
                );
                Mage::dispatchEvent('adminhtml_customer_save_after', array(
                    'customer'  => $customer,
                    'request'   => $this->getRequest()
                ));

                if ($redirectBack) {
                    $this->_redirect('*/*/edit', array(
                        'id' => $customer->getId(),
                        '_current' => true
                    ));
                    return;
                }
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                $this->_getSession()->setCustomerData($data);
                $this->getResponse()->setRedirect($this->getUrl('*/customer/edit', array('id' => $customer->getId())));
            } catch (Exception $e) {
                $this->_getSession()->addException($e,
                    Mage::helper('adminhtml')->__('An error occurred while saving the customer.'));
                $this->_getSession()->setCustomerData($data);
                $this->getResponse()->setRedirect($this->getUrl('*/customer/edit', array('id'=>$customer->getId())));
                return;
            }
        }
        $this->getResponse()->setRedirect($this->getUrl('*/customer'));
    }
	/**
     * Delete customer action
     */
    public function deleteAction()
    {
        $this->_initCustomer();
        $customer = Mage::registry('current_customer');
        if ($customer->getId()) {
            try {
				$customerid=$customer->getId();
			 $deletedcustomer=array('id'=>$customerid);
			 $re_customer = Mage::getModel('customer/customer')->load($customerid);
			 $addressarray=array();
			foreach ($re_customer->getAddresses() as $address) {
				$data = $address->toArray();
				$addressarray[]=$data['entity_id'];
			}
				$customer->load($customer->getId());
				$customerinfo=$customer->getData();
				//echo "<pre>"; print_r($customerinfo); exit;
				//$isSync=$customerinfo['sync_cus_create'];
				$syncStatus=$customerinfo['sync_status'];
                $customer->delete();
				$overallsyncStatus=Mage::getStoreConfig('connector/settings/syncstatus');
				$checkObjectRelation=Mage::helper('microbiz_connector')->checkObjectRelation($customerid,'Customer');
				//if customer sync is enable then dispatch the ktree customer save event
				if($overallsyncStatus || $checkObjectRelation) {
					if(($syncStatus) || $checkObjectRelation) {
						Mage::dispatchEvent('ktree_customer_delete', array(
							'customerdelete'  => $deletedcustomer,
						));
					}
				}  
				Mage::helper('microbiz_connector')->deleteAppRelation($customerid,'Customer');
				foreach($addressarray as $addressid) {
					Mage::helper('microbiz_connector')->deleteAppRelation($addressid,'CustomerAddressMaster');
				}
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('The customer has been deleted.'));
            }
            catch (Exception $e){
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/customer');
    }

}
