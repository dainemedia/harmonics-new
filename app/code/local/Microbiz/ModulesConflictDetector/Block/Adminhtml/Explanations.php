<?php
/**
 * @author    Marcin Frymark
 * @email     contact@microbiz.com
 * @company   Microbiz
 * @website   www.microbiz.com
 */
class Microbiz_ModulesConflictDetector_Block_Adminhtml_Explanations extends Mage_Adminhtml_Block_Template
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('microbizModulesConflictDetector/explanations.phtml');
    }
    
    public function getConflictColor()
    {
        return Microbiz_ModulesConflictDetector_Model_Rewrites::CONFLICT_COLOR;
    }
    
    public function getNoConflictColor()
    {
        return Microbiz_ModulesConflictDetector_Model_Rewrites::NO_CONFLICT_COLOR;
    }

    public function getConflictResolvedColor()
    {
        return Microbiz_ModulesConflictDetector_Model_Rewrites::RESOLVED_CONFLICT_COLOR;
    }    
}
