<?php

/**
 * RocketWeb
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   RocketWeb
 * @package    RocketWeb_GoogleBaseFeedGenerator
 * @copyright  Copyright (c) 2012 RocketWeb (http://rocketweb.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     RocketWeb
 */

class RocketWeb_GoogleBaseFeedGenerator_Block_Adminhtml_System_Config_Form_Field_Findandreplace extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract {

    public function __construct() {

        $this->addColumn('find', array(
            'label' => Mage::helper('adminhtml')->__('Find'),
            'style' => 'width:200px',
        ));

        $this->addColumn('replace', array(
            'label' => Mage::helper('adminhtml')->__('Replace'),
            'style' => 'width:200px',
        ));

        $this->addColumn('columns', array(
            'label' => Mage::helper('adminhtml')->__('Map Columns'),
            'style' => 'width:100px',
            'class' => 'validate-greater-than-zero',
        ));

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add');

        parent::__construct();
        $this->setTemplate('googlebasefeedgenerator/system/config/form/field/array.phtml');
    }

    public function attributesToJson() {
        $json = array('attributes' => array());
        return Zend_Json::encode($json);
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
        if ($columnName == 'columns') {
            $html .= '<select name="'.$inputName.'" '.(isset($column['style']) ? ' style="'.$column['style'] . '"' : '').'>';
            foreach ($this->getMapColumns() as $option) {
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

    /**
     * Get the current columns in map
     *
     * @return array
     */
    protected function getMapColumns() {

        $return = array(array('label' => Mage::helper('googlebasefeedgenerator')->__('- All Columns -'), 'value' => ''));
        $mapKey = 'rocketweb_googlebasefeedgenerator/columns/map_product_columns';
        $config = $this->getConfigData();

        if ($config && array_key_exists($mapKey, $config)) {
            $columns = unserialize($config[$mapKey]);
            if (is_array($columns)) {
                foreach ($columns as $column) {
                    array_push($return, array('label' => $column['column'], 'value' => $column['column']));
                }
            }
        }
        return $return;
    }
}