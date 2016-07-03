<?php

class TM_FireCheckout_Block_Adminhtml_Form_Field_Timerange extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        if (!$this->_beforeToHtml()) {
            return '';
        }

        $html = '<select name="' . $this->getName() . '[]" ' . $this->getExtraParams() . '>';
        for ($i = 0; $i < 24; $i++) {
            $hour = str_pad($i, 2, '0', STR_PAD_LEFT);
            $html .= $this->_optionToHtml(array(
                'value' => $hour,
                'label' => $hour,
                'salt'  => 'hour'
            ));
        }
        $html .= '</select>';

        $html .= '&nbsp;:&nbsp;<select name="' . $this->getName() . '[]" ' . $this->getExtraParams() . '>';
        for ($i = 0; $i < 60; $i++) {
            $hour = str_pad($i, 2, '0', STR_PAD_LEFT);
            $html .= $this->_optionToHtml(array(
                'value' => $hour,
                'label' => $hour,
                'salt'  => 'minute'
            ));
        }
        $html .= '</select>';

        $column = $this->getColumn();
        $html = '<div style="' . $column['style'] . '">' . $html . '</div>';
        return $html;
    }

    /**
     * Return option HTML node
     *
     * @param array $option
     * @param boolean $selected
     * @return string
     */
    protected function _optionToHtml($option)
    {
        $selectedHtml = ' #{option_extra_attr_' . self::calcOptionHash($option['value'], $option['salt']) . '}';

        return sprintf(
            '<option value="%s"%s>%s</option>',
            $this->htmlEscape($option['value']),
            $selectedHtml,
            $this->htmlEscape($option['label'])
        );
    }

    public function getHtml()
    {
        return $this->toHtml();
    }

    public function calcOptionHash($optionValue, $optionSalt = null)
    {
        return sprintf('%u', crc32($this->getName() . $this->getId() . $optionValue . $optionSalt));
    }

    public function setInputName($value)
    {
        return $this->setName($value);
    }
}
