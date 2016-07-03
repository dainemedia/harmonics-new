<?php
// Version 100
class Microbiz_Connector_Block_Adminhtml_Synchistory extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	public function __construct()
	{
		$this->_controller = 'adminhtml_synchistory';
		$this->_blockGroup = 'microbiz_connector';
		
		 ///////CUSTOM code for new button:
       $data = array(
               'label' =>  'Clear Sync History',
               'onclick'   => "setLocation('".$this->getUrl('connector/adminhtml_connectordebug/clearsynchistory')."')"
               );
       ///////The URL I am using is a custom module that I set up earlier, Magento parses it to <MySite.com/shop/index.php/downloadtomas>, which then runs the script I have in the IndexController.php file
       Mage_Adminhtml_Block_Widget_Container::addButton('clear_history_data', $data, 0, 100,  'header', 'header');
       ///////End CUSTOM code
		
		$this->_headerText = Mage::helper('microbiz_connector')->__('Sync History Information');
		parent::__construct();
		$this->_removeButton('add');
	}
}
