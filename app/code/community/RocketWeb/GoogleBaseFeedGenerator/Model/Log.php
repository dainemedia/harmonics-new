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

class RocketWeb_GoogleBaseFeedGenerator_Model_Log
{
	CONST WRITER_DEFAULT = 1;
	CONST WRITER_MEMORY = 2;
	
	protected $_memory_storage = array();
	private $_max_msgs = 100;
	private $_maxed = false;
	
	public function log($msg, $level = null, $writer = null, $options = array())
	{
		if (is_null($level))
    		$level = Zend_Log::INFO;
    	if (is_null($writer))
    		$writer = self::WRITER_DEFAULT;
    	
    	switch ($writer) {
    		case self::WRITER_DEFAULT:
    			$this->logDefault($msg, $level, $options);
    			break;
    		case self::WRITER_MEMORY:
    			$this->logMemory($msg, $level, $options);
    			break;
    		
    		default:
    			$this->logDefault($msg, $level, $options);
    	}
    	return $this;
	}
	
	protected function logDefault($msg, $level = null, $options = array())
	{
		$file = '';
		if (isset($options['file']) && $options['file'] != "") {
			$file = $options['file'];
		}
		$force = false;
		if (isset($options['file']) && $options['file'] != false) {
			$force = (bool) $options['file'];
		}
		Mage::log($msg, $level, $file, $force);
		return $this;
	}
	
	protected function logMemory($msg, $level = null, $options = array())
	{
		if ($this->_maxed && is_array($this->_memory_storage)) {
			$this->_memory_storage = array_shift($this->_memory_storage);
		}
		if (!is_array($this->_memory_storage)) {
            $this->_memory_storage = array($this->_memory_storage);
        }
        array_push($this->_memory_storage,$msg);

		if (!$this->_maxed && count($this->_memory_storage) > $this->_max_msgs) {
			$this->_maxed = true;
		}

		return $this;
	}
	
	public function getMaxMsgs()
	{
		return $this->_max_msgs;
	}
	
	public function getMemoryStorage()
	{
		return $this->_memory_storage;
	}
	
}
