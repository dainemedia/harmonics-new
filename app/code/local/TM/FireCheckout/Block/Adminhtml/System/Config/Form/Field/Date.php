<?php

class TM_FireCheckout_Block_Adminhtml_System_Config_Form_Field_Date extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    /**
     * @var TM_FireCheckout_Block_Adminhtml_Form_Field_Date
     */
    protected $_dateRenderer;

    /**
     * Retrieve time column renderer
     *
     * @return TM_FireCheckout_Block_Adminhtml_Form_Field_Date
     */
    protected function _getDateRenderer()
    {
        if (!$this->_dateRenderer) {
            $this->_dateRenderer = $this->getLayout()->createBlock(
                'firecheckout/adminhtml_form_field_date'
            );
        }
        return $this->_dateRenderer;
    }

    protected function _prepareToRender()
    {
        $this->addColumn('date', array(
            'label'    => Mage::helper('adminhtml')->__('Date'),
            'renderer' => $this->_getDateRenderer(),
            'style'    => 'width: 230px;'
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
        $date     = $row->getDate();
        $renderer = $this->_getDateRenderer();
        foreach (array('month', 'day', 'year') as $i => $period) {
            $row->setData(
                'option_extra_attr_' . $renderer->calcOptionHash($date[$i], $period),
                'selected="selected"'
            );
        }
    }
}
