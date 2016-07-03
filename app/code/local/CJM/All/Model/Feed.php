<?php
Class CJM_All_Model_Feed extends Mage_AdminNotification_Model_Feed {

    const XML_FEED_URL_PATH_CJM = 'system/cjm_all/feed_url_cjm';
	protected $_CJMfeed;

   	public function getLastUpdate()
    {
        return Mage::app()->loadCache('cjm_notifications_lastcheck');
    }

    public function setLastUpdate()
    {
        Mage::app()->saveCache(time(), 'cjm_notifications_lastcheck');
        return $this;
    }

    public function checkUpdate()
    {
        if (($this->getFrequency() + $this->getLastUpdate()) > time()) {
            return $this;
        }

        $feedData = array();
        $feedXml = $this->getFeedData();

        if ($feedXml && $feedXml->channel && $feedXml->channel->item) {
            foreach ($feedXml->channel->item as $item) {
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

    public function getFeedUrl()
    {
        if (is_null($this->_CJMfeed)) {
            $this->_CJMfeed = (Mage::getStoreConfigFlag(self::XML_USE_HTTPS_PATH) ? 'https://' : 'http://'). Mage::getStoreConfig(self::XML_FEED_URL_PATH_CJM); }
        return $this->_CJMfeed;
    }

    public function getFeedData()
    {
        $curl = new Varien_Http_Adapter_Curl();
        $curl->setConfig(array(
            'timeout'   => 3
        ));

        $curl->write(Zend_Http_Client::GET, $this->getFeedUrl(), '1.0');
        $data = $curl->read();
        if ($data === false) {
            return false;
        }
        $data = preg_split('/^\r?$/m', $data, 2);
        $data = trim($data[1]);
        $curl->close();

        try {
            $xml  = new SimpleXMLElement($data);
        }
        catch (Exception $e) {
            return false;
        }

        return $xml;
    }

}


?>
