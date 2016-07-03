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

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_System_Config_Form_Field_Mapemptycolumns extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract {

	public function __construct() {

        $this->addColumn('column', array(
            'label' => Mage::helper('adminhtml')->__('For Empty Feed Column'),
            'style' => 'width:200px',
        ));

        $this->addColumn('static', array(
            'label' => Mage::helper('adminhtml')->__('Replace with Static Value'),
            'style' => 'width:200px',
        ));
        
        $this->addColumn('attribute', array(
            'label' => Mage::helper('adminhtml')->__('Replace with Attribute Value'),
            'style' => 'width:300px',
        ));

        $this->addColumn('rule_order', array(
            'label' => Mage::helper('adminhtml')->__('Rule Order'),
            'style' => 'width:30px',
            'class' => 'validate-greater-than-zero',
        ));
        
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add Rule');
        parent::__construct();
        $this->setTemplate('googlebasefeedgenerator/system/config/form/field/array.phtml');
    }
    
    /**
     * Forms array for select values: attribute_code => attribute_label.
     *
     * @return array
     */
    protected function getProductAttributesCodes() {

    	$store_id = null;
    	if (($store_code = $this->getRequest()->getParam('store')) != "")
    		$store_id = Mage::app()->getStore($store_code)->getStoreId();
    	$ret = Mage::getSingleton('googlebasefeedgenerator/config')->getProductAttributesCodes($store_id, false);
    	foreach ($ret as $key => $value) {
    		$ret[$key] = addslashes($value);
    	}
    	array_unshift($ret, '- static value -');
        return $ret;
    }
    
    public function getFeedColumns() {

		$feed_columns = array();
		$Stores = Mage::app()->getStores();
		$config = Mage::getSingleton('googlebasefeedgenerator/config');
		foreach ($Stores as $Store) {
			$cfg_map_product_columns = $config->getConfigVar('map_product_columns', $Store->getStoreId(), 'columns');
			if (is_array($cfg_map_product_columns))
    			foreach ($cfg_map_product_columns as $arr) {
    				if (isset($arr['column']) && !isset($feed_columns[$arr['column']])) {
    					$feed_columns[$arr['column']] = $arr['column'];
    				}
    			}
		}
        return $feed_columns;
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
        if ($columnName == 'attribute') {
            $html .= '<select name="'.$inputName.'" '.(isset($column['style']) ? ' style="'.$column['style'] . '"' : '').'>';
            foreach ($this->getProductAttributesCodes() as $value => $label) {
				$html .= '<option label="'.$label.'" value="'.$value.'">'.$label.'</option>';
            }
            $html .= '</select>';
        } elseif ($columnName == 'column') {
            $html .= '<select name="'.$inputName.'" '.(isset($column['style']) ? ' style="'.$column['style'] . '"' : '').'>';
            foreach ($this->getFeedColumns() as $value => $label) {
				$html .= '<option label="'.$label.'" value="'.$value.'">'.$label.'</option>';
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
}