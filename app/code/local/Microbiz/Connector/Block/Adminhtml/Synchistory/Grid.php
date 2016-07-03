<?php
// Version 100
class Microbiz_Connector_Block_Adminhtml_Synchistory_Grid extends Mage_Adminhtml_Block_Widget_Grid
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
		
		$collection = Mage::getModel('syncheaderhistory/syncheaderhistory')->getCollection();
		$this->setCollection($collection);
		
		return parent::_prepareCollection();
	}
	protected function _prepareColumns()
	{
		$this->addColumn('header_id', array(
    'header' => Mage::helper('microbiz_connector')->__('Header ID'),
    'align' =>'right',
    'width' => '50px',
    'index' => 'header_id',
    ));
    $this->addColumn('instance_id', array(
    'header' => Mage::helper('microbiz_connector')->__('Instance Id'),
    'align' =>'left',
    'index' => 'instance_id',
    ));
	$this->addColumn('model_name', array(
    'header' => Mage::helper('microbiz_connector')->__('Model'),
    'align' =>'left',
    'index' => 'model_name',
    ));
	$this->addColumn('obj_id', array(
    'header'    => Mage::helper('microbiz_connector')->__('Object Id'),
    'align' =>'left',
    'index'     => 'obj_id',
	));
	$this->addColumn('status', array(
    'header' => Mage::helper('microbiz_connector')->__('Status'),
    'align' =>'left',
    'index' => 'status',
    ));
	
    
    
		return parent::_prepareColumns();
	}
	public function getRowUrl($row)
	{
		return $this->getUrl('*/*/viewsynchistory', array('id' => $row->getHeaderId()));
	}
}
