<?php
/**
 * @author    Marcin Frymark
 * @email     contact@microbiz.com
 * @company   Microbiz
 * @website   www.microbiz.com
 */
class Microbiz_ModulesConflictDetector_Model_Rewrites extends Mage_Core_Model_Abstract
{
    const NO_CONFLICT_TYPE       = 0;
    const RESOLVED_CONFLICT_TYPE = 1;
    const CONFLICT_TYPE          = 2;
    
    const CONFLICT_COLOR          = 'red';
    const NO_CONFLICT_COLOR       = 'green';
    const RESOLVED_CONFLICT_COLOR = 'grey';
    
    const USE_CACHE = true;
    const CACHE_TYPE = "config";
    const CACHE_KEY = "KTREE_MODULES_CONFLICTS";
    
    public function useCache()
    {
        return Mage::app()->useCache(self::CACHE_TYPE) && self::USE_CACHE;
    }

    public function getTypes()
    {
        $types = array(
                    'helper' => 'Helper',
                    'block'  => 'Block',
                    'model'  => 'Model',
                 );

        return $types;
    }

    public function getConflictTypes()
    {
        $types = array(
                    self::NO_CONFLICT_TYPE       => Mage::helper('microbiz_modulesConflictDetector')->__('No'),
                    self::CONFLICT_TYPE          => Mage::helper('microbiz_modulesConflictDetector')->__('Yes'),
                    self::RESOLVED_CONFLICT_TYPE => Mage::helper('microbiz_modulesConflictDetector')->__('Resolved'),
                );

        return $types;
    }    

    public function getRewritesCollection()
    {
        if ($this->useCache() && $cache = Mage::app()->loadCache(self::CACHE_KEY)) {
            $collection = unserialize($cache);
        } else {
            $collection = Mage::getModel('microbiz_modulesConflictDetector/resource_collection');

            foreach($this->_getRewritesArray() as $rewrite) {
                $collection->addItem($rewrite);
            }
            
            if ($this->useCache()) {
                Mage::app()->saveCache(serialize($collection), self::CACHE_KEY, array(self::CACHE_TYPE));
            }
        }        

        return $collection;
    }

    protected function _getRewritesArray()
    {
        $rewritesArray = array();

        foreach($this->getTypes() as $type => $label) {
            $rewrites = $this->_collectRewrites($type);
            foreach($rewrites as $initialClass => $rewritesData) {
                $rewriteItem = new Varien_Object();
                $rewriteItem->setClass($initialClass);                
                $rewriteItem->setType($type);
                $rewriteItem->setRewrites($rewritesData);                
                $rewriteItem->setConflict($this->_getConflict($rewritesData['classes']));

                $rewritesArray[] = $rewriteItem;
            }
        }

        return $rewritesArray;
    }

    protected function _collectRewrites($type)
    {
        $modelsRewrites = array();
        
        $modules = Mage::getConfig()->getNode('modules')->children();
        foreach ($modules as $modName=>$module) {
            if (!$module->is('active')) {
                continue;
            }
            
            $configFile = Mage::getConfig()->getModuleDir('etc', $modName) . DS . 'config.xml';
            $moduleConfig = Mage::getModel('core/config_base');
            $moduleConfig->loadString('<config/>');
            $moduleConfigBase = Mage::getModel('core/config_base');
            $moduleConfigBase->loadFile($configFile);
            $moduleConfig->extend($moduleConfigBase, true);

            $typeNode = $moduleConfig->getNode()->global->{$type.'s'};
            
            if (!$typeNode) {
                continue;
            }
   
            $nodes = $typeNode->children();
            foreach($nodes as $nodeName => $config) {
                $rewrites = $config->rewrite;
            
                if ($rewrites) {
                    $classes = array();
                    foreach($rewrites->children() as $classId => $newClass) {
                        $classes[] = (string)$newClass;
                        $initialClass = $this->_getClassName($type, $nodeName, $classId);
                        $usedRewrite = $this->_getClassName($type, $nodeName, $classId, true);
                        
                        $color = false;
                        
                        if ($newClass == $usedRewrite) {
                            $color = self::NO_CONFLICT_COLOR;
                            $conflict = self::NO_CONFLICT_TYPE;
                        } elseif (is_subclass_of($usedRewrite, $newClass)) {
                            $color = self::RESOLVED_CONFLICT_COLOR;
                            $conflict = self::RESOLVED_CONFLICT_TYPE;
                        } else {
                            $color = self::CONFLICT_COLOR;
                            $conflict = self::CONFLICT_TYPE;
                        }
                        
                        $modelsRewrites[$initialClass]['classes'][] = array(
                                                                            'class'    => (string)$newClass,
                                                                            'color'    => $color,
                                                                            'conflict' => $conflict,
                                                                        );
                        if (!isset($modelsRewrites[$initialClass]['filter_condition'])) {
                            $modelsRewrites[$initialClass]['filter_condition'] = (string)$newClass . ' ';
                        } else {
                            $modelsRewrites[$initialClass]['filter_condition'] .= (string)$newClass . ' ';
                        }
                    }
                }
            }
        }

        return $modelsRewrites;
    }
    
    protected function _getClassName($type, $groupId, $classId, $rewrites = false)
    {
        $config = Mage::getConfig()->getNode()->global->{$type.'s'}->{$groupId};        
        
        if ($rewrites && isset($config->rewrite->$classId)) {
            $className = (string)$config->rewrite->$classId;
        } else {        
            if (!empty($config)) {
                $className = $config->getClassName();
            }
            if (empty($className)) {
                $className = 'mage_'.$groupId.'_'.$type;
            }
            if (!empty($classId)) {
                $className .= '_'.$classId;
            }
            $className = uc_words($className);    
        }
    
        return $className;
    }
    
    protected function _getConflict($rewritesData)
    {
        $conflict = self::NO_CONFLICT_TYPE;
        foreach($rewritesData as $rewrite) {
            if ($rewrite['conflict'] == self::CONFLICT_TYPE) {
                return self::CONFLICT_TYPE;
            } elseif ($rewrite['conflict'] == self::RESOLVED_CONFLICT_TYPE) {
                $conflict = self::RESOLVED_CONFLICT_TYPE;
            }
        }
        return $conflict;
    }
}
