<?php
// Version 100
class Microbiz_Connector_Block_Adminhtml_Storeinventory_Grid extends Mage_Adminhtml_Block_Widget_Grid
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
		
		$collection = Mage::getModel('storeinventory/storeinventory')->getCollection();
		$this->setCollection($collection);
		
		return parent::_prepareCollection();
	}
	protected function _prepareColumns()
	{
		$this->addColumn('storeinventory_id', array(
    'header' => Mage::helper('microbiz_connector')->__('Id'),
    'align' =>'right',
    'width' => '50px',
    'index' => 'storeinventory_id',
    ));
    $this->addColumn('instance_id', array(
    'header' => Mage::helper('microbiz_connector')->__('Instance Id'),
    'align' =>'left',
    'index' => 'instance_id',
    ));
	$this->addColumn('material_id', array(
    'header' => Mage::helper('microbiz_connector')->__('Product Id'),
    'align' =>'left',
    'index' => 'material_id',
    ));
	$this->addColumn('company_id', array(
    'header'    => Mage::helper('microbiz_connector')->__('Company Id'),
    'align' =>'left',
    'index'     => 'company_id',
	));
	$this->addColumn('store_id', array(
    'header' => Mage::helper('microbiz_connector')->__('Store Id'),
    'align' =>'left',
    'index' => 'store_id',
    ));
	$this->addColumn('quantity', array(
    'header' => Mage::helper('microbiz_connector')->__('Quantity'),
    'align' =>'left',
    'index' => 'quantity',
    ));
    $this->addColumn('stock_type', array(
    'header' => Mage::helper('microbiz_connector')->__('Stock Type'),
    'align' =>'left',
    'index' => 'stock_type',
    ));
    
		return parent::_prepareColumns();
	}
	public function getRowUrl($row)
	{
		//return $this->getUrl('*/*/view', array('id' => $row->getId()));
	}
}
