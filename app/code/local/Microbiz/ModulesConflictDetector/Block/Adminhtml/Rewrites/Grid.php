<?php
/**
 * @author    Marcin Frymark
 * @email     contact@microbiz.com
 * @company   Microbiz
 * @website   www.microbiz.com
 */
class Microbiz_ModulesConflictDetector_Block_Adminhtml_Rewrites_Grid extends Mage_Adminhtml_Block_Widget_Grid
{ 

   public function __construct()
    {
        parent::__construct();
        $this->setId('modules_rewrites_grid');
        $this->setSaveParametersInSession(true);
        $this->setDefaultSort('conflict');
        $this->setDefaultDir('asc');        
        $this->setUseAjax(true);
        $this->setPagerVisibility(false);
    }

	protected function _prepareColumns()
    {
    
	    $this->addColumn('class', 
			array(
				'header' => Mage::helper('microbiz_modulesConflictDetector')->__('Initial Class'),
				'index'  => 'class',
        ));
        
	    $this->addColumn('type', 
			array(
				'header'  => Mage::helper('microbiz_modulesConflictDetector')->__('Type'),
				'index'   => 'type',
                'type'    => 'options',
                'options' => Mage::getSingleton('microbiz_modulesConflictDetector/rewrites')->getTypes(),
        ));  

        $this->addColumn('rewrites', 
			array(
				'header'  => Mage::helper('microbiz_modulesConflictDetector')->__('Rewrites'),
				'index'   => 'rewrites',
                'renderer'=> 'microbiz_modulesConflictDetector/adminhtml_rewrites_grid_renderer_rewrites',
        ));
        
        $this->addColumn('conflict', 
			array(
				'header'  => Mage::helper('microbiz_modulesConflictDetector')->__('Conflict'),
				'index'   => 'conflict',
                'type'    => 'options',
                'options' => Mage::getSingleton('microbiz_modulesConflictDetector/rewrites')->getConflictTypes(),
        ));

		return parent::_prepareColumns();
	}

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('microbiz_modulesConflictDetector/rewrites')->getRewritesCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    public function getRowUrl($item)
    {
        return false;
    }    
    
}
