<?php
/**
 * @author    Marcin Frymark
 * @email     contact@microbiz.com
 * @company   Microbiz
 * @website   www.microbiz.com
 */
 class Microbiz_ModulesConflictDetector_Block_Adminhtml_Rewrites extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {
        $this->_blockGroup = 'microbiz_modulesConflictDetector';
        $this->_controller = 'adminhtml_rewrites';
        $this->_headerText = Mage::helper('microbiz_modulesConflictDetector')->__('Modules Conflict Detector');
        parent::__construct();
        $this->removeButton('add');
    }

}
