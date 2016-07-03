<?php

class TM_FireCheckout_Block_Adminhtml_System_Config_Form_Fieldset_AddressFormOrder
    extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    /**
     * Return header html for fieldset
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getHeaderHtml($element)
    {
        if ($element->getIsNested()) {
            $html = '<tr class="nested"><td colspan="4"><div class="' . $this->_getFrontendClass($element) . '">';
        } else {
            $html = '<div class="' . $this->_getFrontendClass($element) . '">';
        }

        $html .= $this->_getHeaderTitleHtml($element);

        $html .= '<input id="'.$element->getHtmlId() . '-state" name="config_state[' . $element->getId()
            . ']" type="hidden" value="' . (int)$this->_getCollapseState($element) . '" />';
        $html .= '<fieldset class="' . $this->_getFieldsetCss($element) . '" id="' . $element->getHtmlId() . '">';
        $html .= '<legend>' . $element->getLegend() . '</legend>';

        $html .= $this->_getHeaderCommentHtml($element);

        // sortable fields modification
        $html .= $this->_getDragNDropFields($element);
        // sortable fields modification

        // field label column
        $html .= '<table id="address_form_order_classic" cellspacing="0" class="form-list"><colgroup class="label" /><colgroup class="value" />';
        if ($this->getRequest()->getParam('website') || $this->getRequest()->getParam('store')) {
            $html .= '<colgroup class="use-default" />';
        }
        $html .= '<colgroup class="scope-label" /><colgroup class="" /><tbody>';

        return $html;
    }

    protected function _getDragNDropFields($element)
    {
        $html = '';
        $html .=
            '<div class="firecheckout-sort-order-mode">'
            . '<a id="firecheckout_toggle_dragdrop" href="javascript:void(0)">' . $this->__('Switch to classic mode') . '</a>'
            . '</div>'
            . '<div id="firecheckout_sort_order_wrapper" style="display:block;">'
            . '<div id="address_form_order_dragdrop">';
        $i = 1;
        foreach ($element->getSortedElements() as $field) {
            $html.= '<div class="address-field ' . $field->getId() . '" id="' . $field->getId() . '_draggable_' . $i++ . '">'
                 . $field->getLabel()
                 . '<span class="move-handle" title="' . $this->__('Move') . '"></span>'
                 . '<span class="new-line-trigger" title="' . $this->__('Add newline') . '"></span>'
                 . '</div>';
        }
        $html .= '</div>'
            . '<div id="address_form_order_dragdrop_overlay" style="display:block;"></div>'
            . '</div>'
            . $this->_getSortOrderJs()
            . '<div class="clear"></div>'
            . '<div class="firecheckout-sort-order-mode" style="display: block;">'
            . '<a id="firecheckout_toggle_classic" href="javascript:void(0)">' . $this->__('Switch to drag&amp;drop mode') . '</a>'
            . '</div>';

        return $html;
    }

    protected function _getSortOrderJs()
    {
        $js = <<<JS
document.observe("dom:loaded", function() {
    var addressSort = new AddressSort({
        dragdrop: 'address_form_order_dragdrop',
        classic : 'address_form_order_classic'
    });
});
JS;
        return Mage::helper('adminhtml/js')->getScript($js);
    }

    /**
     * Get frontend class
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getFrontendClass($element)
    {
        $frontendClass = (string)$this->getGroup($element)->frontend_class;
        return 'section-config' . (empty($frontendClass) ? '' : (' ' . $frontendClass));
    }

    /**
     * Get group xml data of the element
     *
     * @param null|Varien_Data_Form_Element_Abstract $element
     * @return Mage_Core_Model_Config_Element
     */
    public function getGroup($element = null)
    {
        if (is_null($element)) {
            $element = $this->getElement();
        }
        if ($element && $element->getGroup() instanceof Mage_Core_Model_Config_Element) {
            return $element->getGroup();
        }

        return new Mage_Core_Model_Config_Element('<config/>');
    }

    /**
     * Return header title part of html for fieldset
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getHeaderTitleHtml($element)
    {
        return '<div class="entry-edit-head collapseable" ><a id="' . $element->getHtmlId()
            . '-head" href="#" onclick="Fieldset.toggleCollapse(\'' . $element->getHtmlId() . '\', \''
            . $this->getUrl('*/*/state') . '\'); return false;">' . $element->getLegend() . '</a></div>';
    }

    /**
     * Return header comment part of html for fieldset
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getHeaderCommentHtml($element)
    {
        return $element->getComment()
            ? '<div class="comment">' . $element->getComment() . '</div>'
            : '';
    }

    /**
     * Return full css class name for form fieldset
     *
     * @param null|Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getFieldsetCss($element = null)
    {
        $configCss = (string)$this->getGroup($element)->fieldset_css;
        return 'config collapseable' . ($configCss ? ' ' . $configCss : '');
    }

    /**
     * Return footer html for fieldset
     * Add extra tooltip comments to elements
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getFooterHtml($element)
    {
        $tooltipsExist = false;
        $html = '</tbody></table>';
        $html .= '</fieldset>' . $this->_getExtraJs($element, $tooltipsExist);

        if ($element->getIsNested()) {
            $html .= '</div></td></tr>';
        } else {
            $html .= '</div>';
        }
        return $html;
    }
}
