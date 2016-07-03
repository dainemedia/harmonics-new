<?php
/**
 * @author    Marcin Frymark
 * @email     contact@microbiz.com
 * @company   Microbiz
 * @website   www.microbiz.com
 */
class Microbiz_ModulesConflictDetector_Model_Resource_Collection extends Microbiz_ModulesConflictDetector_Model_Resource_NonDbCollection
{
    protected function _getColumnsValue($item, $column)
    {
        if ($column == 'rewrites') {
            $data = $item->getData($column);
            $result = false;
            
            if (!isset($data['classes'])) {
                return $result;
            }
            
            $classes = $data['classes'];
            
            foreach($classes as $class) {
                if (!$result || $class['conflict'] == Microbiz_ModulesConflictDetector_Model_Rewrites::NO_CONFLICT_TYPE) {
                    $result = $class['class'];
                }
            }

            return $result;
        } else {
            return parent::_getColumnsValue($item, $column);
        }
    }
}
