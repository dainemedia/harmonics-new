<?php
// Version 100
class Microbiz_Connector_Block_Adminhtml_Mbizattributeoption extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	public function __construct()
	{
		$this->_controller = 'adminhtml_mbizattributeoption';
		$this->_blockGroup = 'microbiz_connector';
		$this->_headerText = Mage::helper('microbiz_connector')->__('Attribute Option Relations');
		parent::__construct();
		$this->_removeButton('add');
	}
}
