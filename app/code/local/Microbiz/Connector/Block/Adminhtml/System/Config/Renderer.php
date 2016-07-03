<?php
class Microbiz_Connector_Block_Adminhtml_System_Config_Renderer extends Mage_Adminhtml_Block_System_Config_Form_Field{

    /**
     * Returns html part of the setting
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */


    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $originalData = $element->getOriginalData();

        $value = $element->getEscapedValue();
        $value = ($element->getHtmlId() =='carriers_mbizdelevery_price') ? "Use MicroBiz Delivery Rates" : "0";
        $html = '<input id="'.$element->getHtmlId().'" name="'.$element->getName()
            .'" value="'.$value.'" '.$element->serialize($element->getHtmlAttributes()).' disabled/>'."\n";
        $html.= $element->getAfterElementHtml();
        return $html;
    }
}
