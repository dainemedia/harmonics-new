<?php
/**
 * @author    Marcin Frymark
 * @email     contact@microbiz.com
 * @company   Microbiz
 * @website   www.microbiz.com
 */
class Microbiz_ModulesConflictDetector_Block_Adminhtml_Rewrites_Grid_Renderer_DetailsButton extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $url = $this->getGridUrl($row->getClass());
        $label = Mage::helper('microbiz_modulesConflictDetector')->__('Show Details');
        return '<a href="' . $url . '">' . $label . '</a>';
    }
    
    public function getGridUrl($class = null)
    {
        return $this->getUrl('*/*/details', array('class' => $class));
    }    
}
