<?php

class TM_CheckoutFields_Block_Adminhtml_System_Config_Form_Field_Options extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    /**
     * @var TM_CheckoutFields_Block_Adminhtml_Form_Field_Options
     */
    protected $_optionRenderer;

    /**
     * Retrieve option column renderer
     *
     * @return TM_CheckoutFields_Block_Adminhtml_Form_Field_Options
     */
    protected function _getOptionRenderer()
    {
        if (!$this->_optionRenderer) {
            $this->_optionRenderer = $this->getLayout()->createBlock(
                'checkoutfields/adminhtml_form_field_options'
            );
        }
        return $this->_optionRenderer;
    }

    protected function _prepareToRender()
    {
        $this->addColumn('option', array(
            'label'    => Mage::helper('adminhtml')->__('Options'),
            'renderer' => $this->_getOptionRenderer(),
            'style'    => 'width: 180px'
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
}
