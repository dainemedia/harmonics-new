<?php
// Version 100
class Microbiz_Connector_Block_Adminhtml_Connectorcataloginventory_Grid extends Mage_Adminhtml_Block_Widget_Grid
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
		
		$collection = Mage::getModel('connectorcataloginventory/connectorcataloginventory')->getCollection();
		$this->setCollection($collection);
		
		return parent::_prepareCollection();
	}
	protected function _prepareColumns()
	{
		$this->addColumn('item_id', array(
    'header' => Mage::helper('microbiz_connector')->__('ID'),
    'align' =>'right',
    'width' => '50px',
    'index' => 'item_id',
    ));
    $this->addColumn('product_id', array(
    'header' => Mage::helper('microbiz_connector')->__('Product Id'),
    'align' =>'left',
    'index' => 'product_id',
    ));
	$this->addColumn('stock_id', array(
    'header' => Mage::helper('microbiz_connector')->__('Stock Id'),
    'align' =>'left',
    'index' => 'stock_id',
    ));
	$this->addColumn('qty', array(
    'header'    => Mage::helper('microbiz_connector')->__('Quantity'),
    'align' =>'left',
    'index'     => 'qty',
	));
		
    
    
		return parent::_prepareColumns();
	}
	public function getRowUrl($row)
	{
		//return $this->getUrl('*/*/view', array('id' => $row->getId()));
	}
}
