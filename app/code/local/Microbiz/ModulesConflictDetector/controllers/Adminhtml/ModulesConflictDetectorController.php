<?php
/**
 * @author    Marcin Frymark
 * @email     contact@microbiz.com
 * @company   Microbiz
 * @website   www.microbiz.com
 */
class Microbiz_ModulesConflictDetector_Adminhtml_ModulesConflictDetectorController extends Mage_Adminhtml_Controller_Action
{

    public function indexAction()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->_forward('grid');
            return;
        }    
    
        $this->loadLayout();
        $this->_setActiveMenu('system/modules_conflict_detector');  
        $this->_addContent(
            $this->getLayout()->createBlock('microbiz_modulesConflictDetector/adminhtml_rewrites', 'modules_rewrites')
        );
        $this->_addContent(
            $this->getLayout()->createBlock('microbiz_modulesConflictDetector/adminhtml_explanations', 'explanations')
        );    
        $this->renderLayout();
    }
    
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody($this->getLayout()->createBlock('microbiz_modulesConflictDetector/adminhtml_rewrites_grid')->toHtml());
    }
}
