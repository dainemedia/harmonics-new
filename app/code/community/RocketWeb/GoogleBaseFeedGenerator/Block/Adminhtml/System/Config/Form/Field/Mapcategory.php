<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   RocketWeb
 * @package    RocketWeb_GoogleBaseFeedGenerator
 * @copyright  Copyright (c) 2012 RocketWeb (http://rocketweb.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     RocketWeb
 */


/**
 * Adminhtml system config attributes array field renderer
 *
 * @category   RocketWeb
 * @package    RocketWeb_GoogleBaseFeedGenerator
 */

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_System_Config_Form_Field_Mapcategory extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract {

	public function __construct() {

        $this->addColumn('category', array(
            'label' => Mage::helper('adminhtml')->__('Category'),
            'style' => 'width:300px',
        ));
        
        $this->addColumn('value', array(
            'label' => Mage::helper('adminhtml')->__('Static Value'),
            'style' => 'width:250px',
        ));
        
        $this->addColumn('order', array(
            'label' => Mage::helper('adminhtml')->__('Order'),
            'style' => 'width:30px',
            'class' => 'validate-greater-than-zero',
        ));
        
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add');

        parent::__construct();
        $this->setTemplate('googlebasefeedgenerator/system/config/form/field/array.phtml');
    }
    
    /**
     * Forms array for select values: attribute_code => attribute_label.
     *
     * @return array
     */
    protected function getAllCategories() {

        $options = array();
        $store_id = ($this->getForm()->getScope() == 'stores') ? $this->getForm()->getScopeId() : null;

    	$_categories = Mage::getSingleton('googlebasefeedgenerator/config')->getAllCategories($store_id);
		foreach ($_categories as $id => $categ) {
			if (isset($categ['name']) && isset($categ['level'])) {
				if ($categ['level'] < 1)
					$categ['level'] = 1;

				$options[] = array(
					'value' => $id,
					'label' =>  addslashes($categ['name']),
					'style' => 'padding-left:' . (($categ['level'] - 1) * 7) . 'px;',
				);
			}
		}
        return $options;
    }
    
    /**
     * Render array cell for prototypeJS template
     *
     * @param string $columnName
     * @return string
     */
    protected function _renderCellTemplate($columnName) {

        if (empty($this->_columns[$columnName])) {
            throw new Exception('Wrong column name specified.');
        }
        $column     = $this->_columns[$columnName];
        $inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

        if ($column['renderer']) {
            return $column['renderer']->setInputName($inputName)->setColumnName($columnName)->setColumn($column)
                ->toHtml();
        }
        
        $html = '';
        if ($columnName == 'category') {
            $html .= '<select name="'.$inputName.'" '.(isset($column['style']) ? ' style="'.$column['style'] . '"' : '').'>';
            foreach ($this->getAllCategories() as $option) {
				$html .= '<option '.(isset($option['style']) ? 'style="'.$option['style'].'" ' : '').'label="'.$option['label'].'" value="'.$option['value'].'">'.$option['label'].'</option>';
            }
            $html .= '</select>';
        } else {
	        $html .= '<input type="text" name="' . $inputName . '" value="#{' . $columnName . '}" ' .
	            ($column['size'] ? 'size="' . $column['size'] . '"' : '') . ' class="' .
	            (isset($column['class']) ? $column['class'] : 'input-text') . '"'.
	            (isset($column['style']) ? ' style="'.$column['style'] . '"' : '') . '/>';
        }
		
		return $html;
    }
    
    public function attributesToJson() {
    	$json = array('attributes' => array());
    	return Zend_Json::encode($json);
    }
    
    public function hasDefaultValueBehaviour() {
    	return false;
    }

    public function getCategoryTreeJson() {
        return json_encode(Mage::getSingleton('googlebasefeedgenerator/config')->getCategoriesTree());
    }
}