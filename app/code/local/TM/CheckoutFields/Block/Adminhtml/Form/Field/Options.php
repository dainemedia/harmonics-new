<?php

class TM_CheckoutFields_Block_Adminhtml_Form_Field_Options extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        if (!$this->_beforeToHtml()) {
            return '';
        }

        $html = '<input type="text" class="input-text" style="width: 170px;" name="' . $this->getName() . '" value="#{option}"/>';

        $column = $this->getColumn();
        $html = '<div style="' . $column['style'] . '">' . $html . '</div>';
        return $html;
    }

    public function getHtml()
    {
        return $this->toHtml();
    }

    public function setInputName($value)
    {
        return $this->setName($value);
    }
}
