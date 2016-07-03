<?php

/**
 * Place this script in /[path to magento]/shell/ dir
 * or make a symlink from this script to shell dir.
 */

require_once 'abstract.php';

/**
 * Google Shopping Feed Generator Shell Script
 *
 * @category    GoogleBaseFeedGenerator
 * @package     GoogleBaseFeedGenerator_Shell
 */
class GoogleBaseFeedGenerator_Shell_Feed extends Mage_Shell_Abstract
{

    /**
     * Run script
     *
     */
    public function run()
    {
    	try {
    		$data = array();
            $data['schedule_id']    = uniqid(rand(), true);
            $data['mage_cron']      = false;
            $data['verbose']        = $this->getArg('verbose') ? true : false;
            $data['missing_img']    = $this->getArg('missing_img') ? true : false;
            $data['store_code']     = $this->getArg('store_code') ? $this->getArg('store_code') : Mage_Core_Model_App::DISTRO_STORE_CODE;
            $data['batch_mode']     = $this->getArg('batch_mode') ? true : false;
            $data['test_mode']      = $this->getArg('test_mode') ? true : false;
            $data['test_sku']       = $this->getArg('test_sku') ? $this->getArg('test_sku') : false;
            $data['test_limit']     = $this->getArg('test_limit') ? $this->getArg('test_limit') : 0;
            $data['test_offset']    = $this->getArg('test_offset') ? $this->getArg('test_offset') : 0;

	        @Mage::app('admin')->setUseSessionInUrl(false);
	        
	        set_time_limit(0);
			/* Setting memory limit depends on the number of products exported.*/
			// ini_set('memory_limit','600M');
			error_reporting(E_ALL);

            try {
                $store_id = Mage::app()->getStore($data['store_code'])->getStoreId();
            } catch (Exception $e) {
                Mage::throwException(sprintf('Store with code \'%s\' doesn\'t exists.', $data['store_code']));
            }

			$Generator = Mage::getSingleton('googlebasefeedgenerator/tools')->addData($data)->getGenerator($store_id);
            $Generator->run();

    	} catch (Exception $e) {
    		echo $e->getMessage() . PHP_EOL;
    	}
    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php gen_gbase_feed.php --[options]

  store_code <string>       Store Code (e.g. [STORE_CODE] or default). Store must exist and should be enabled.
                            By default uses 'default' value.
  batch_mode <int>          Segment the feed generation. Values accepted: 0 or 1. Default is 0.
  test_mode <int>           Enable test mode or not. Default is 0.
  test_sku <string>         Generate the feed only for a product sku. To be used for tests and debuging.
  test_limit <int>          Sql limit parameter in test mode. Is applied to the select of the collection of products.
  test_offset <int>         Sql offset parameter in test mode. Is applied to the select of the collection of products.
  verbose                   Outputs skus and memory during processing
  missing_img               Soft check on images - don't have to be physical files in media/catalog
  help                      This help
                            e.g. php gen_gbase_feed.php --store_code [STORE_CODE] --batch_mode 1 --verbose 1

USAGE;
    }
}

$shell = new GoogleBaseFeedGenerator_Shell_Feed();
$shell->run();