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

class RocketWeb_GoogleBaseFeedGenerator_Model_Observer
{
	
	protected $schedule;
	
	/**
	 * @param Mage_Cron_Model_Schedule $schedule
	 */
    public function generateFeed($schedule)
    {
    	$this->schedule = $schedule;
    	$store_id = 1;
    	$store_code	= 'default';
    	
    	$jobs_root = Mage::getConfig()->getNode('crontab/jobs');
    	$job_config = $jobs_root->{$this->schedule->getJobCode()};
    	$store_code = (string) $job_config->store;
    	if (empty($store_code))
    		$store_code = Mage_Core_Model_Store::DEFAULT_CODE;
    	
    	$store_id = Mage::app()->getStore($store_code)->getStoreId();
        $Generator = Mage::getSingleton('googlebasefeedgenerator/tools')->addData(array(
                'store_code'   => $store_code,
                'batch_mode'   => true,
                'schedule_id' => $this->schedule->getScheduleId(),
                'mage_cron'    => true,
            ))
            ->getGenerator($store_id);
        
    	// No exceptions to break cron by this extension.
    	try {
    		$Generator->run();
    		// No lock => No execution or queue done => Don't add new jobs.
    		if ($Generator->getBatchMode() && !$Generator->getBatch()->getLocked()) {
    			$jobs_estimate = $Generator->getBatch()->getEstimateJobs($this->getBatchJobs());
    			$this->scheduleJobs($jobs_estimate);
    		}
    	} catch (Exception $e) {
    		$force_log = (($force_log = Mage::getStoreConfig('rocketweb_googlebasefeedgenerator/settings/force_log', $store_id)) ? true : false);
    		Mage::log($e->getMessage(),
    				  Zend_Log::ERR,
    				  sprintf(Mage::getStoreConfig('rocketweb_googlebasefeedgenerator/settings/log_filename', $store_id), $store_code),
    				  $force_log);
    	}
    }
    
    /**
     * Gets count of script runs of current queue during current day.
     *
     * @return int
     */
    public function getBatchJobs()
    {
    	$collection = Mage::getModel('cron/schedule')->getCollection();
    	/** @var $collection Mage_Cron_Model_Mysql4_Schedule_Collection */
    	$collection->addFieldToFilter('job_code', $this->schedule->getJobCode())
    		->addFieldToFilter('status', array('in' => array(
	    		Mage_Cron_Model_Schedule::STATUS_PENDING,
	    		Mage_Cron_Model_Schedule::STATUS_SUCCESS,
	    		Mage_Cron_Model_Schedule::STATUS_RUNNING,
	    	)))
    	->addFieldToFilter('scheduled_at', array('gteq' => date('Y-m-d 00:00:00')))
    	->addFieldToFilter('scheduled_at', array('lteq' => date('Y-m-d 23:59:59')));
    	$count = $collection->getSize();
    	return $count;
    }
    
    public function scheduleJobs($estimate)
    {
    	if ($estimate == 0)
    		return;
    	if ($estimate < 0) {
    		// There are too many jobs.
    		$collection = Mage::getModel('cron/schedule')->getCollection()
	    		->addFieldToFilter('job_code', $this->schedule->getJobCode())
	            ->addFieldToFilter('status', array('in'=>array(
	                Mage_Cron_Model_Schedule::STATUS_PENDING,
	            )))
	            ->addFieldToFilter('scheduled_at', array('gteq' => date('Y-m-d 00:00:00')))
    			->addFieldToFilter('scheduled_at', array('lteq' => date('Y-m-d 23:59:59')))
    			->load();
            
            foreach ($collection as $Schedule)
            	$Schedule->delete();
    	} elseif ($estimate > 0) {
    		// Schedule more jobs.
			for ($i = 1; $i <= $estimate; $i++) {
    			$Schedule = Mage::getModel('cron/schedule');
    			$Schedule->setJobCode($this->schedule->getJobCode());
    			$Schedule->setStatus(Mage_Cron_Model_Schedule::STATUS_PENDING);
    			$Schedule->setCreatedAt(date('Y-m-d H:i:s', time()));
    			$Schedule->setScheduledAt(date('Y-m-d H:i:s', time() + $i * (Mage::getStoreConfig(Mage_Cron_Model_Observer::XML_PATH_SCHEDULE_LIFETIME) * 60)));
    			$Schedule->save();
			}
    	}
    }
}