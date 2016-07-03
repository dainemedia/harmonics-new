<?php

class CJM_CustomStockStatus_Block_System_Config_About extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{
	public function render(Varien_Data_Form_Element_Abstract $element)
	{
		
		$thismodule = 'CJM_CustomStockStatus'; //CHANGES
		
		try {
    		
    		$feed = curl_init('http://chadjmorgan.com/magedev/CJM_MageFeed.xml');
    		
    		if($feed === false){
    			throw new Exception('Error loading module info feed.'); }
    
    		curl_setopt($feed, CURLOPT_RETURNTRANSFER, true);
    		curl_setopt($feed, CURLOPT_HEADER, 0);
    		$xml = curl_exec($feed);
    		curl_close($feed);
    	
    		if($xml === false){
    			throw new Exception('Error loading module info XML.'); }
    	
    		$result = new SimpleXMLElement($xml);
    		
    		if (!$result || !$result->channel->item) {
      			throw new Exception('No info in module info XML.'); }
      			
      			
      		$htmlFeed = curl_init('http://chadjmorgan.com/magedev/about.html');
      		
      		if($htmlFeed === false){
    			throw new Exception('Error loading about section HTML.'); }
    		
    		curl_setopt($htmlFeed, CURLOPT_RETURNTRANSFER, true);
    		curl_setopt($htmlFeed, CURLOPT_HEADER, 0);
    		$html = curl_exec($htmlFeed);
    		curl_close($htmlFeed);
    		
    		if($html === false || $html == ''){
    			throw new Exception('Error loading about section HTML or there is no content.'); }

      		foreach ($result->channel->item as $item) {
    			if($item->name == $thismodule) {
					$modulename = strtoupper($item->title);
					$html = str_replace('%%MODNAME%%', $modulename, $html);
					$moduleversion = Mage::getConfig()->getNode("modules/".$thismodule."/version");
					$html = str_replace('%%MODVERS%%', $moduleversion, $html);
					$newestversion = $item->version;
					$html = str_replace('%%MODNEWEST%%', $newestversion, $html);
					break;
				}
			}

    		unset($feed, $xml, $result);
    		
    		return $html;
    	
		} catch (Exception $e) {
      	
      		return $e->getMessage();
  		}
    }
}
