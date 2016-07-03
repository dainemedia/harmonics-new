<?php
/**
 * @author    Marcin Frymark
 * @email     contact@microbiz.com
 * @company   Microbiz
 * @website   www.microbiz.com
 */
class Microbiz_ModulesConflictDetector_Block_Adminhtml_Rewrites_Grid_Renderer_Rewrites extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {        
        $rewrites = $row->getRewrites();
        $result = array();

        foreach($rewrites['classes'] as $rewrite) {                
            if (isset($rewrite['color'])) {
                $result[] = '<span style="color:' . $rewrite['color'] . ';">' . $rewrite['class'] . '</span>';
            } else {
                $result[] = $rewrite['class'];
            }
        }      

        return implode('<br/>', $result);
    }
}
