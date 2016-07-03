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

class RocketWeb_GoogleBaseFeedGenerator_Model_Batch extends Varien_Object {

	protected $total_items;
	protected $offset = 0;
	protected $limit = 1000;
	protected $started_at = 0;
	protected $ended_at = 0;

    public function _construct() {

		parent::_construct();
		$this->started_at = time(); // Mage::getSingleton('core/date')->timestamp(time());
		$this->setData('cdate', $this->started_at);
	}

    /**
     * @return bool
     */
    public function aquireLock() {

		if (!file_exists($this->getLockPath())) {
			$f = @fopen($this->getLockPath(), "w");
			@fclose($f);
			if (!file_exists($this->getLockPath()))
				Mage::throwException(sprintf('Can\'t create file', $this->getLockPath()));
		}
		
		if (($this->_locked = $this->isLocked()) == true) {
			$this->log(sprintf('Can\'t aquire batch lock for script [%s]', $this->getScheduleId()));
			return false;
		}
		
		$this->lock();
		return true;
	}

    /**
     * @return bool
     */
    public function isLocked() {

		$locked = false;
		$lock_data = $this->readFile();
		// Locked if another script is running.
		if ($lock_data['id'] != $this->getScheduleId() && $lock_data['status'] == Mage_Cron_Model_Schedule::STATUS_RUNNING) {
			$this->log(sprintf('Script [%s] is already running', $lock_data['id']));
			// Aquire lock if expired (expires in almost 24 hours)
			if (!$this->isLockExpired($lock_data['queue_started_at'])) {
				$locked = true;
			} else {
				$this->log(sprintf(
					'Lock of script [%s] has expired at %s, script [%s] will aquire lock (cdate is [%s])',
					$lock_data['id'],
					date('Y-m-d H:i:s', date('Y-m-d 23:59:59', $this->getCdate())),
					$this->getScheduleId(),
					date('Y-m-d H:i:s', $this->started_at)
				));
			}
		}
		
		// Fail safe - don't allow to run too many times even if cron missed or unknown error was triggered in the past.
		if ($lock_data['offset'] > 0 && $lock_data['fail_safe'] >= ceil($this->total_items / $this->limit) + max(ceil(ceil($this->total_items / $this->limit) / 2), 2)) {
			$locked = true;
			if (!$this->isLockExpired($lock_data['queue_started_at'])) {
				$locked = false;
				$this->log(sprintf('Script was executed too many times %d', $lock_data['fail_safe']));
			}
		}
		
		// Allow only 1 complete feed generation in a single day.
		if ($this->getQueueFinished($lock_data['offset'], $this->total_items) && !$this->isLockExpired($lock_data['queue_started_at'])) {
			$locked = true;
			$this->log(sprintf('Script %s can\'t run. The feed was completly generated once today %s', $this->getScheduleId(), date('Y-m-d')));
		}
		
		return $locked;
	}

    /**
     * @param $time
     * @return bool
     */
    public function isLockExpired($time) {

		$expired = true;
		$cdate = $this->getCdate();
		$cday_s = mktime(0, 0, 0, date('m', $cdate), date('d', $cdate), date('Y', $cdate));
		$cday_e = mktime(23, 59, 59, date('m', $cdate), date('d', $cdate), date('Y', $cdate));

		if ($time >= $cday_s && $time <= $cday_e) {
			$expired = false;
        }

        return $expired;
	}

    /**
     * @return $this
     */
    protected function lock() {

		$lock_data = $this->readFile();
		
		$this->setIsNew(false);
		if ($lock_data['offset'] == 0)
			$this->setIsNew(true);
			
		if ($this->getQueueFinished($lock_data['offset'], $this->total_items)) {
			$lock_data = $this->resetLockData();
			$this->setIsNew(true);
		}
		
		$lock_data['id'] = $this->getScheduleId();
		// Declare as processed to prevent loops by incrementing offset from the begining.
		$this->offset = $this->getNextOffset($lock_data['offset'], $this->limit);
		$lock_data['offset'] = $this->offset;
		$lock_data['total'] = $this->total_items;
		$lock_data['limit'] = $this->limit;
		$lock_data['started_at'] = $this->started_at;
		$lock_data['ended_at'] = 0;
		$lock_data['status'] = Mage_Cron_Model_Schedule::STATUS_RUNNING;
		$lock_data['fail_safe'] += 1;
		
		$this->writeFile($lock_data);
		$this->log(sprintf('Script %d aquired lock', $this->getScheduleId()));
		return $this;
	}

    /**
     * @return bool
     */
    public function releaseLock() {

		$this->unlock();
		return true;
	}

    /**
     * @param $offset
     * @param $total
     * @return bool
     */
    public function getQueueFinished($offset, $total) {

		if ($offset >= $total)
			return true;
		return false;
	}

    /**
     * @return $this
     */
    protected function unlock() {

		$lock_data = $this->readFile();
		$lock_data['ended_at'] = time();
		$lock_data['status'] = Mage_Cron_Model_Schedule::STATUS_SUCCESS;
		$this->writeFile($lock_data);
		$this->log(sprintf('Script %d released lock', $this->getScheduleId()));
		return $this;
	}

    /**
     * @param $offset
     * @param $limit
     * @return mixed
     */
    protected function getNextOffset($offset, $limit) {
        return $offset + $limit;
    }

    /**
     * @param $past_runs
     * @return float|mixed
     */
    public function getEstimateJobs($past_runs) {

		$estimate = ceil($this->total_items / $this->limit) - $past_runs;
		$estimate = min($estimate, ceil($this->total_items / $this->limit) - $this->offset / $this->limit);
		return $estimate;
	}

    /**
     * @param $mixed
     * @return mixed
     */
    public function writeFile($mixed) {

		$mixed['updated_at'] = time();
		if (!is_writable($this->getLockPath()))
			Mage::throwException(sprintf('Can\t write to %s', $this->getLockPath()));
		if (file_put_contents($this->getLockPath(), @serialize($mixed), LOCK_EX) === false)
			Mage::throwException(sprintf('Error writing to %s', $this->getLockPath()));
		return $mixed;
	}

    /**
     * @return array|mixed|string
     */
    public function readFile() {

		$mixed = file_get_contents($this->getLockPath());
		if ($mixed === false)
			Mage::throwException(sprintf('Error reading from %s', $this->getLockPath()));
		$mixed = @unserialize($mixed);
		if (empty($mixed)) {
			$mixed = $this->resetLockData();
			$mixed['created_at'] = time();
		}
		return $mixed;
	}

    /**
     * @return array
     */
    protected function resetLockData() {

		return array(
			'id'	      => false,
			'offset'	  => 0,
			'total'	 	  => $this->total_items,
			'limit'	 	  => $this->limit,
			'status'	  => Mage_Cron_Model_Schedule::STATUS_PENDING,
			'started_at'  => 0,
			'ended_at'	  => 0,
			'updated_at'  => 0,
			'queue_started_at' => time(),
			'fail_safe'	  => 0);
	}

    /**
     * @return string
     */
    public function getLockPath() {
        return $this->getGenerator()->getBatchLockPath();
    }

    /**
     * @param $value
     */
    public function setTotalItems($value) {
        $this->total_items = (int) $value;
    }

    /**
     * @param $value
     */
    protected function setOffset($value) {
        $this->offset = (int) $value;
    }

    /**
     * @return int
     */
    public function getOffset() {
        return $this->offset;
    }

    /**
     * @param $value
     */
    public function setLimit($value) {
        $this->limit = (int) $value;
    }

    /**
     * @return int
     */
    public function getLimit() {
        return $this->limit;
    }

    /**
     * @return mixed
     */
    public function getLocked() {
        return $this->_locked;
    }
    
    /**
     * @param string $key
     * @param string $section
     * @return mixed
     */
    public function getConfigVar($key, $section = 'settings') {
    	return $this->getGenerator()->getConfigVar($key, $section);
    }
    
    /**
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Config
     */
    public function getConfig() {
    	return $this->getGenerator()->getConfig();
    }

    /**
     * @param $msg
     * @param null $level
     */
    public function log($msg, $level = null) {
    	$this->getGenerator()->log($msg, $level);
    }
    
    /**
     * Singleton by $SchedulerId of generator class
     *
     * @return RocketWeb_GoogleBaseFeedGenerator_Model_Generator
     */
    public function getGenerator() {

        $registryKey = '_singleton/googlebasefeedgenerator/generator_'. $this->getScheduleId();

        if (!Mage::registry($registryKey)) {
            Mage::register($registryKey, Mage::getModel('googlebasefeedgenerator/generator', array(
                'store_code'    => $this->getStoreCode(),
                'store_id'      => $this->getStoreId(),
                'website_id'    => $this->getWebsiteId(),
                'schedule_id'   => $this->getScheduleId()
            )));
        }

        return Mage::registry($registryKey);
    }
}