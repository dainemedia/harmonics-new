<?php
/**
 * @author    Marcin Frymark
 * @email     contact@microbiz.com
 * @company   Microbiz
 * @website   www.microbiz.com
 */
class Microbiz_ModulesConflictDetector_Model_Resource_NonDbCollection extends Varien_Data_Collection
{
    protected $_filters = array();

    public function addFieldToFilter($attribute, $condition=null)
    {
        if (isset($condition['eq'])) {
            $value  = $condition['eq'];
            $method = 'equal';
        } elseif (isset($condition['like'])) {
            $value  = $condition['like'];
            $method = 'like';        
        }
        
        $this->_filters[] = array(
                            'attribute' => $attribute,
                            'method'    => $method,
                            'value'     => $value,
                        );        
    }
    
    public function load($printQuery = false, $logQuery = false)
    {
        if ($this->isLoaded()) {
            return $this;
        }    

        $this->_addFilterToCollection();               
        $this->_sortCollection();
        $this->_renderLimit();
        
        $this->_setIsLoaded();
        return $this;
    }
    
    public function getSize()
    {
        if (is_null($this->_totalRecords)) {
            $this->_totalRecords = count($this->_items);
        }
        return intval($this->_totalRecords);
    }
    
    protected function _renderLimit()
    {
        if($this->_pageSize){
            $from = ($this->getCurPage() - 1) * $this->_pageSize;
            $to  =  ($this->getCurPage() - 1) * $this->_pageSize + $this->_pageSize - 1;
            $items = $this->_items;
            $this->_items = array();
            $counter = 0;
            foreach($items as $item) {
                if ($counter >= $from && $counter <= $to) {
                    $this->addItem($item);
                }
                $counter++;
            }
        }

        return $this;
    }
    
    protected function _sortCollection()
    {
        usort($this->_items , array($this, '_doCompare'));
        return $this;
    }
    
    protected function _addFilterToCollection()
    {
        $items = $this->_items;
        $this->_items = array();
        foreach($items as $item) {
            if ($this->_filterItem($item)) {
                $this->addItem($item);
            }
        }
    }
    
    protected function _filterItem($item)
    {
        foreach ($this->_filters as $filter) {
            $method = $filter['method'];
            $attribute = $filter['attribute'];
            $itemValue = $item[$attribute];
            
            if (is_array($itemValue) && isset($itemValue['filter_condition'])) {
                $itemValue = $itemValue['filter_condition'];
            }

            if (!$this->$method($itemValue, $filter['value'])) {
                return false;
            }
        }
        
        return true;
    }
 
    protected function _getColumnsValue($item, $column)
    {
        $value = $item->getData($column);
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        return $value;
    }
 
    protected function _doCompare($a, $b)
    {
        foreach($this->_orders as $column => $order) {                                    
            $valueA = $this->_getColumnsValue($a, $column);
            $valueB = $this->_getColumnsValue($b, $column);

            $result = strcmp($valueA, $valueB);

            if (strtolower($order) == 'asc') {
                $result = -$result;
            }
            
            return $result;
        }
        return 0;
    }
 
    public function equal($filerValue, $needle)
    {
        return ($filerValue == $needle);
    }
    
    public function like($filerValue, $needle)
    {
        $needle = trim($needle, ' \'"%');
        return stristr($filerValue, $needle);
    }    
}
