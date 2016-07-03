<?php
// Version 100
class Microbiz_Connector_Block_Adminhtml_Mbizattributeoption_Grid extends Mage_Adminhtml_Block_Widget_Grid
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
		
		$collection = Mage::getModel('mbizattributeoption/mbizattributeoption')->getCollection();
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
	$this->addColumn('magento_id', array(
    'header' => Mage::helper('microbiz_connector')->__('Magento Id'),
    'align' =>'left',
    'index' => 'magento_id',
    ));
	$this->addColumn('mbiz_id', array(
    'header'    => Mage::helper('microbiz_connector')->__('MicroBiz ID'),
    'align' =>'left',
    'index'     => 'mbiz_id',
	));

	$this->addColumn('mbiz_attr_code', array(
    'header' => Mage::helper('microbiz_connector')->__('MicroBiz Attribute Id'),
    'align' =>'left',
    'index' => 'mbiz_attr_id',
    ));

    
    
		return parent::_prepareColumns();
	}
	public function getRowUrl($row)
	{
		//return $this->getUrl('*/*/edit', array('id' => $row->getId()));
	}
}
