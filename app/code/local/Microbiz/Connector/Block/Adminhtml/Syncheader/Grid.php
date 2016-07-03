<?php
// Version 100
class Microbiz_Connector_Block_Adminhtml_Syncheader_Grid extends Mage_Adminhtml_Block_Widget_Grid
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
		
		$collection = Mage::getModel('extendedmbizconnector/extendedmbizconnector')->getCollection();
		$this->setCollection($collection);
		
		return parent::_prepareCollection();
	}
	protected function _prepareColumns()
	{
		$this->addColumn('id', array(
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

        $this->addColumn('action', array(
                'header' => Mage::helper('microbiz_connector')->__('Action'),
                'width' => '100',
                'type' => 'action',
                'getter' => 'getId',
                'actions' => array(
                    array(
                        'caption' => Mage::helper('microbiz_connector')->__('View'),
                        'url' => array('base'=> '*/*/view'),
                        'field' => 'id'
                    ),
                    array(
                        'caption' => Mage::helper('microbiz_connector')->__('Process'),
                        'url' => array('base'=> '*/*/processRecord'),
                        'field' => 'id'
                    )
                ),
                'filter' => false,
               'sortable' => false,
                'index' => 'action',
                'is_system' => true,
            )
        );
        return parent::_prepareColumns();

	}
	public function getRowUrl($row)
	{
		return $this->getUrl('*/*/view', array('id' => $row->getId()));
	}
}
