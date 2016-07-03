<?php
//version 101
/**
 * @author    Marcin Frymark
 * @email     contact@microbiz.com
 * @company   Microbiz
 * @website   www.microbiz.com
 */
class Microbiz_ModulesConflictDetector_Model_MicrobizAdminNotification_Feed extends Mage_AdminNotification_Model_Feed
{
    const XML_ENABLED_PATH = 'microbiz_adminNotification/general/enabled';
    const XML_FREQUENCY_PATH = 'microbiz_adminNotification/general/frequency';
    const NOTIFICANTION_LASTCHECK_CACHE_KEY = 'microbiz_notifications_lastcheck';

    protected $_microbizInstalledModules;
    
    public function getFeedUrl()
    {
        if (version_compare(Mage::getVersion(), '1.7.0', '<='))
        {
            parent::getFeedUrl();
        }

        if (is_null($this->_feedUrl)) {
            $this->_feedUrl = Mage::helper('microbiz_modulesConflictDetector')->getMicrobizUrl() . '/rss/magento_rss.xml';
            $query = '?utm_source=' . urlencode(Mage::getStoreConfig('web/unsecure/base_url'));
            $query .= '&utm_medium=' . urlencode('Magento Connect');
            $query .= '&utm_content=' . urlencode(Mage::getEdition() . ' ' . Mage::getVersion());
            $query .= '&utm_term=' . urlencode(implode(',', $this->_getMicrobizInstalledModules()));
            
            $this->_feedUrl .= $query;
        }
      
        return $this->_feedUrl;
    }
    
    public function checkUpdate()
    {
        if (!Mage::getStoreConfig(self::XML_ENABLED_PATH)) {
            return $this;
        }
    
        if (($this->getFrequency() + $this->getLastUpdate()) > time()) {
            return $this;
        }

        $feedData = array();
        $feedXml = $this->getFeedData();

        if ($feedXml && $feedXml->channel && $feedXml->channel->item) {
            foreach ($feedXml->channel->item as $item) {
            
                $module = (string)$item->module;
                if ($module && !in_array($module, $this->_getMicrobizInstalledModules())) {
                    continue;
                }
            
                $feedData[] = array(
                    'severity'      => (int)$item->severity,
                    'date_added'    => $this->getDate((string)$item->pubDate),
                    'title'         => (string)$item->title,
                    'description'   => (string)$item->description,
                    'url'           => (string)$item->link,
                );
            }

            if ($feedData) {
                Mage::getModel('adminnotification/inbox')->parse(array_reverse($feedData));
            }

        }
        $this->setLastUpdate();

        return $this;
    }

    public function getLastUpdate()
    {
        return Mage::app()->loadCache(self::NOTIFICANTION_LASTCHECK_CACHE_KEY);
    }
    
    public function setLastUpdate()
    {
        Mage::app()->saveCache(time(), self::NOTIFICANTION_LASTCHECK_CACHE_KEY);
        return $this;
    }
    
    protected function _getMicrobizInstalledModules()
    {
        if (is_null($this->_microbizInstalledModules)) {
            $modules = array_keys((array)Mage::getConfig()->getNode('modules')->children());
            $this->_microbizInstalledModules = array();
            foreach ($modules as $moduleName) {
                if (substr($moduleName, 0, 9) == 'Microbiz_'){
                    $this->_microbizInstalledModules[] = $moduleName;
                }
            }
        }
        return $this->_microbizInstalledModules;
    }
}
