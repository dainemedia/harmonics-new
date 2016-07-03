<?php
// Version 100
class Microbiz_Connector_Block_Adminhtml_Connectordebug_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	public function __construct()
	{
		
		parent::__construct();
		$this->setId('connectorGrid');
		$this->setDefaultSort('id');
		$this->setDefaultDir('ASC');
		$this->setSaveParametersInSession(true);
	}
	protected function _prepareCollection()
	{
		
		$collection = Mage::getModel('connectordebug/connectordebug')->getCollection();
		$this->setCollection($collection);
		
		return parent::_prepareCollection();
	}
	protected function _prepareColumns()
	{
		$this->addColumn('id', array(
    'header' => Mage::helper('microbiz_connector')->__('ID'),
    'align' =>'right',
    'width' => '50px',
    'index' => 'id',
    ));
    $this->addColumn('instance_id', array(
    'header' => Mage::helper('microbiz_connector')->__('Instance Id'),
    'align' =>'left',
    'index' => 'instance_id',
    ));
	$this->addColumn('status', array(
    'header' => Mage::helper('microbiz_connector')->__('Staus'),
    'align' =>'left',
    'index' => 'status',
    ));
	$this->addColumn('status_msg', array(
    'header'    => Mage::helper('microbiz_connector')->__('Status Information'),
    'align' =>'left',
    'index'     => 'status_msg',
	));
    
    
		return parent::_prepareColumns();
	}
	public function getRowUrl($row)
	{
		//return $this->getUrl('*/*/edit', array('id' => $row->getId()));
	}
}
