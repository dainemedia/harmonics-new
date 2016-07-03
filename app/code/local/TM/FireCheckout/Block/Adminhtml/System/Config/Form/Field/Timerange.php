<?php

class TM_FireCheckout_Block_Adminhtml_System_Config_Form_Field_Timerange extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    /**
     * @var TM_FireCheckout_Block_Adminhtml_Form_Field_Timerange
     */
    protected $_timeRenderer = array();

    /**
     * Retrieve time column renderer
     *
     * @return TM_FireCheckout_Block_Adminhtml_Form_Field_Timerange
     */
    protected function _getTimeRenderer($type = 'from')
    {
        if (empty($this->_timeRenderer[$type])) {
            $timeRenderer = $this->getLayout()->createBlock(
                'firecheckout/adminhtml_form_field_timerange'
            );
            $timeRenderer->setExtraParams('style="width:40px"');
            $this->_timeRenderer[$type] = $timeRenderer;
        }
        return $this->_timeRenderer[$type];
    }

    protected function _prepareToRender()
    {
        $this->addColumn('from', array(
            'label'    => Mage::helper('adminhtml')->__('From'),
            'renderer' => $this->_getTimeRenderer('from'),
            'style'    => 'width: 95px'
        ));
        $this->addColumn('to', array(
            'label'    => Mage::helper('adminhtml')->__('To'),
            'renderer' => $this->_getTimeRenderer('to'),
            'style'    => 'width: 95px'
        ));
        $this->_addAfter = false;
    }

    /**
     * Overrided to add wrapper with id attribute. Fix for the depends option.
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $html = parent::_getElementHtml($element);
        return '<div id="' . $element->getHtmlId() . '">' . $html . '</div>';
    }

    /**
     * Prepare existing row data object
     *
     * @param Varien_Object
     */
    protected function _prepareArrayRow(Varien_Object $row)
    {
        foreach (array('from', 'to') as $time) {
            $data     = $row->getData($time);
            $renderer = $this->_getTimeRenderer($time);
            foreach (array('hour', 'minute') as $i => $type) {
                $row->setData(
                    'option_extra_attr_' . $renderer->calcOptionHash($data[$i], $type),
                    'selected="selected"'
                );
            }
        }
    }
}
