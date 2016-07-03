<?php
class CJM_CustomStockStatus_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function getHolidays()
	{
		$holidays = explode(',',Mage::getStoreConfig('custom_stock/shipoptions/holidays', Mage::app()->getStore()->getId()));
		$holidays = array_filter(array_map('trim', $holidays));
		$theDates = explode(',',Mage::getStoreConfig('custom_stock/shipoptions/movingholidays', Mage::app()->getStore()->getId()));
		$theDates = array_filter(array_map('trim', $theDates));
		$year = date('o', Mage::getModel('core/date')->timestamp(time()));
		$allDates = array();
		$formattedDates = array();
		
		if(Mage::getStoreConfig('custom_stock/shipoptions/enableholidays', Mage::app()->getStore()->getId())):
		
			if(isset($holidays[0])):
				foreach($holidays as $holiday):
					array_push($allDates, strtotime($year.'-'.$holiday));
				endforeach;
			endif;
		
			if(isset($theDates[0]) && $theDates[0] != ''):
				
				foreach($theDates as $date):
			
					$theParts = explode('-', $date);
					$targetDay = $theParts[0];
					$targetOccurence = $theParts[1];
					$month = $theParts[2];
					
					if($targetOccurence == 'last'):
						$p = 0;
						$number_of_days = date('t', mktime(0, 0, 0, $month, 1, $year));
						for ($i = 1; $i <= $number_of_days; $i++):
        					$weekday = date('N', mktime(0, 0, 0, $month, $i, $year));
        					if ($weekday == $targetDay):
            					$p++;
            				endif;
            			endfor;
						$targetOccurence = $p;
					endif;
			
					$earliestDate = 1 + 7 * ($targetOccurence - 1);
					$theWeekday = date("w", mktime(0,0,0,$month,$earliestDate,$year));
		
					if($targetDay == $theWeekday):
						$theOffset = 0;
					else:
  						if($targetDay < $theWeekday)
  							$theOffset = $targetDay + (7 - $theWeekday);
  						else
  							$theOffset = ($targetDay + (7 - $theWeekday)) - 7;
					endif;
			
					$thisDate = mktime(0,0,0,$month,$earliestDate + $theOffset,$year);
					array_push($allDates, $thisDate);
			
				endforeach;
		
			endif;
		
			asort($allDates);
		
			foreach($allDates as $aDate):
				array_push($formattedDates, date('Y-m-d', $aDate));
			endforeach;
		
			return $formattedDates;
			
		else:
		
			return 'false';
			
		endif;
	}
	
	public function getTheGoods($productId, $data=null)
	{
		$theGoods = $data ? $data : Mage::getModel('catalog/product')->load($productId)->getData();
		
		if(isset($theGoods['cjm_stocktext'])):
       		$stockstatus = $theGoods['cjm_stocktext'];
       	elseif(isset($theGoods['cjm_stockmessage'])):
       		$stockstatus = Mage::getModel('catalog/product')->load($productId)->getAttributeText('cjm_stockmessage');
       	else:
       		$stockstatus = '';
       	endif;
		
       	$stuffs = array(
           	'productId'		=> $productId,
           	'type'			=> $theGoods['type_id'],
           	'isPreorder' 	=> isset($theGoods['cjm_preorderdate']) ? $theGoods['cjm_preorderdate'] : '',
           	'preorderText' 	=> isset($theGoods['cjm_preordertext']) ? $theGoods['cjm_preordertext'] : '',
       		'stockstatus'	=> $stockstatus,
            'isInStock'		=> $theGoods['is_in_stock'],
            'qty'			=> (int)$theGoods['stock_item']->getQty(),
           	'backorder'		=> $theGoods['stock_item']->getBackorders(),
            'managed'		=> $theGoods['stock_item']->getManageStock(),
           	'shipsin'		=> isset($theGoods['cjm_ships_in']) ? $theGoods['cjm_ships_in'] : '',
          	'expected'		=> isset($theGoods['cjm_expecdate']) ? $theGoods['cjm_expecdate'] : '',
          	'ishidden'		=> isset($theGoods['cjm_hideshipdate']) ? $theGoods['cjm_hideshipdate'] : 0,
          	'config_back'	=> $theGoods['stock_item']->getUseConfigBackorders(),
         	'config_stock'	=> $theGoods['stock_item']->getUseConfigManageStock(),
      	);
      	
      	return $stuffs;
	}
	
	public function getListStatus($productId)
	{
		$html = '';
		$start = '';
		$end = '';
		$expecHtml = '';
		$storeId = Mage::app()->getStore()->getId();
		$theGoods = Mage::helper('customstockstatus')->getTheGoods($productId);
		$productkind = $theGoods['type'];
		$stocklevel = $theGoods['qty'];
		$stocked = $theGoods['isInStock'];
		$stockmanaged = $theGoods['config_stock'] == 1 ? Mage::getStoreConfig('cataloginventory/item_options/manage_stock', $storeId) : $theGoods['managed'];
		$expecDate = Mage::helper('customstockstatus')->getHasDateExpired($theGoods['expected'], 'expec');
		
		if($productkind == 'configurable' || $productkind == 'simple' || $productkind == 'virtual'):
			if(($stockmanaged == 1 && $stocked == 0) || (($productkind == 'virtual' || $productkind == 'simple') && $stockmanaged == 1 && $stocklevel <= 0)):
				if($expecDate !== 'true')
					$expecHtml = Mage::helper('customstockstatus')->__('<span class="expected">Expected: %s</span><br>', $expecDate);
			endif;
		endif;
		
		if(Mage::getStoreConfig('custom_stock/general/showonlist', $storeId) == 1):
			
			$start = '<div class="stock-availability">';
			
			switch ($stocked)
			{
   				case 0:
    				$html = $expecHtml ? $expecHtml : '';
        		break;
    			
    			case 1:
    				$availText = Mage::helper('customstockstatus')->getAvailabilityText($theGoods, $productkind);
    				$html = $expecHtml ? $availText.'<br><br>' : $availText;
        		break;
			}
		
			$end = '</div><p class="float-clearer"></p>';
		
		endif;
			
        return $html ? $start.$html.$end : '';
  	}
  	
	public function getConfigExpectedInDate($date)
	{
		$expectDate = Mage::helper('customstockstatus')->getHasDateExpired($date, 'expec');
		return $expectDate !== 'true' ? $expectDate : '';
	}
	
	public function getHasCutoffExpired()
	{
		$cutoffTime = Mage::getStoreConfig('custom_stock/shipoptions/cutofftime', Mage::app()->getStore()->getId());
		date_default_timezone_set(Mage_Core_Model_Locale::DEFAULT_TIMEZONE);
		$now = Mage::getModel('core/date')->timestamp(time());
		
		$currentYear = date('o', $now);
		$currentMonth = date('n', $now);
		$currentDay = date('j', $now);
		$pieces = explode(",", $cutoffTime);
		$cutoffHour = $pieces[0];
		$cutoffMin = $pieces[1];
		
		$cutOffTime = mktime($cutoffHour, $cutoffMin, 0, $currentMonth, $currentDay, $currentYear);
		
		return $now > $cutOffTime ? array('true',$cutOffTime) : array('false',$cutOffTime);
	}
	
	public function getShipsByDate($businessShipDays)
	{
		date_default_timezone_set(Mage_Core_Model_Locale::DEFAULT_TIMEZONE);
		$now = Mage::getModel('core/date')->timestamp(time());
		$holidays = Mage::helper('customstockstatus')->getHolidays();
		$cutoff = Mage::helper('customstockstatus')->getHasCutoffExpired();
		$hasExpired = $cutoff[0];
		
		$weekendStart = Mage::getStoreConfig('custom_stock/shipoptions/saturday', Mage::app()->getStore()->getId()) ? 7 : 6;
		
		if($hasExpired === 'true'):
			$startTime = date('Y-m-d', $now);
			$startTime = strtotime($startTime.' +1 day');
			$startTime = date('Y-m-d', $startTime);
		else:
			$startTime = date('Y-m-d', $now);
		endif;
		 		
		$shipDate = strtotime($startTime);
		
		if($holidays === 'false'):
			
			$i = 1;
			
			while($i < $businessShipDays):
   				$theDay = date('N', $shipDate);
   				$theDate = date('Y-m-d', $shipDate);
   				if($theDay < $weekendStart) $i++;
   				$shipDate = strtotime($theDate.' +1 day'); 
  			endwhile;
		
			$theDay = date('N', $shipDate);
			$theDate = date('Y-m-d', $shipDate);
			
			switch($theDay):
   				
    			case 6: $shipDate = $weekendStart == 6 ? strtotime($theDate.' +2 day') : $shipDate; break;
        		
        		case 7: $shipDate = strtotime($theDate.' +1 day'); break;
			
			endswitch;
		
		else:
		
			$i = 0;
			$m = 0;
			$theDay = date('N', $shipDate);
		
			while($i < $businessShipDays && $m < 30):
   				if($m > 0){
   					$theDate = date('Y-m-d', $shipDate);
   					$shipDate = strtotime($theDate.' +1 day');
   					$theDay = date('N', $shipDate);
   					if($theDay < $weekendStart && !in_array(date('Y-m-d', $shipDate), $holidays)) { $i++; }
   				} else { //For the first time the loop is ran
   					if($businessShipDays == 1){
   						if($hasExpired === 'false' && $theDay < 5){
   							if(in_array(date('Y-m-d', $shipDate), $holidays)) { 
   								$theDate = date('Y-m-d', $shipDate);
   								$shipDate = strtotime($theDate.' +1 day');
   								$theDay = date('N', $shipDate);
   								if($theDay < $weekendStart && !in_array(date('Y-m-d', $shipDate), $holidays)) { $i++; }
   							} else {
   								$i++;
   							}
   						} else {
   							$theDate = date('Y-m-d', $shipDate);
   							//$shipDate = strtotime($theDate.' +1 day');
   							$theDay = date('N', $shipDate);
   							if($theDay < $weekendStart && !in_array(date('Y-m-d', $shipDate), $holidays)) { $i++; }
   						}
   					} else {
   						if($theDay < $weekendStart && !in_array(date('Y-m-d', $shipDate), $holidays)) { $i++; }
   						$theDate = date('Y-m-d', $shipDate);
   						$shipDate = strtotime($theDate.' +1 day');
   						$theDay = date('N', $shipDate);
   						if($theDay < $weekendStart && !in_array(date('Y-m-d', $shipDate), $holidays)) { $i++; }
   					}
   				}
   				$m++;
  			endwhile;
  			
  		endif;
  		
		return $shipDate;
	}
	
	public function getConfigShipDate($shipsin)
	{
		date_default_timezone_set(Mage_Core_Model_Locale::DEFAULT_TIMEZONE);
		$shipbyFormat = Mage::getStoreConfig('custom_stock/configurableproducts/configurableshipbyformat', Mage::app()->getStore()->getId());
		$theDate = date('l F j', Mage::helper('customstockstatus')->getShipsByDate($shipsin));
		$shipDate = strtotime($theDate);
  		$today = date('l F j', Mage::getModel('core/date')->timestamp(time()));
		
		return $theDate == $today ? Mage::helper('customstockstatus')->__('Today') : htmlentities(strftime($shipbyFormat, $shipDate));
	}
	
	public function getConfigurableStockStatus($theGoods, $pk='configurable') //ALSO FOR GROUPED PRODUCTS
	{
		$storeId = Mage::app()->getStore()->getId();
		$productId = $theGoods['productId'];
		$isPreorder = Mage::helper('customstockstatus')->getHasDateExpired($theGoods['isPreorder'], 'preorder');
		$preorderText = str_replace('%pod%', $isPreorder, $theGoods['preorderText']);
		$stockstatus = Mage::helper('customstockstatus')->cleanStockStatus($theGoods);
		$showhightstock = Mage::getStoreConfig('custom_stock/general/showhighstock', $storeId);
		$highstock = Mage::getStoreConfig('custom_stock/general/highstock', $storeId);
		$stocked = $theGoods['isInStock'];
		$backordered = $theGoods['backorder'];
		$qty = $theGoods['qty'];
		
		$qtyOutput = ($highstock && $qty > $highstock && $showhightstock ? Mage::helper('customstockstatus')->__('More than %s ', $highstock) : $qty);
		$shipsin = Mage::helper('customstockstatus')->getShipDays($theGoods['shipsin'], 'simple');
		$expecdate = Mage::helper('customstockstatus')->getConfigExpectedInDate($theGoods['expected']);
		
		if($stocked == 0):
		
			if(!Mage::getStoreConfig('custom_stock/general/outofstock', $storeId)):
				if($expecdate)  
					return Mage::helper('customstockstatus')->__(' * OUT OF STOCK (Expected: %s)', $expecdate);
				else
					return Mage::helper('customstockstatus')->__(' * OUT OF STOCK');
			elseif(Mage::getStoreConfig('custom_stock/'.$pk.'products/'.$pk.'childoutofstock', $storeId) && Mage::getStoreConfig('custom_stock/'.$pk.'products/'.$pk.'childcustomtext', $storeId) && $stockstatus):
				if($expecdate)  
					return Mage::helper('customstockstatus')->__(' * %s (Expected: %s)', $stockstatus, $expecdate);
				else
					return Mage::helper('customstockstatus')->__(' * %s', $stockstatus);
			else:
				if($expecdate)  
					return Mage::helper('customstockstatus')->__(' * OUT OF STOCK (Expected: %s)', $expecdate);
				else
					return Mage::helper('customstockstatus')->__(' * OUT OF STOCK');
			endif;
			
		elseif(Mage::helper('customstockstatus')->getHasDateExpired($isPreorder, 'preorder') !== 'true' && Mage::getStoreConfig('custom_stock/'.$pk.'products/'.$pk.'pre', $storeId)):
			
			return $preorderText ? Mage::helper('customstockstatus')->__(' * %s', $preorderText) : Mage::helper('customstockstatus')->__(' * Pre-Order');
			
		elseif($qty <= 0):
			
			if($backordered == 1)
				return $stockstatus && Mage::getStoreConfig('custom_stock/'.$pk.'products/'.$pk.'childcustomtext', $storeId) ? $stockstatus : '';
			elseif($expecdate)  
				return Mage::helper('customstockstatus')->__(' * On Backorder (Expected: %s)', $expecdate);
			else
				return Mage::helper('customstockstatus')->__(' * On Backorder');
		
		elseif(Mage::getStoreConfig('custom_stock/'.$pk.'products/'.$pk.'childcustomtext', $storeId) && $stockstatus):
			
			return Mage::helper('customstockstatus')->__(' * %s', $stockstatus);
					
		elseif(Mage::getStoreConfig('custom_stock/'.$pk.'products/'.$pk.'shipby', $storeId) && Mage::helper('customstockstatus')->getHasDateExpired($isPreorder, 'preorder') === 'true'):
			
			return Mage::helper('customstockstatus')->__(' * Ships by: %s', Mage::helper('customstockstatus')->getConfigShipDate($shipsin));
				
		elseif(Mage::getStoreConfig('custom_stock/'.$pk.'products/'.$pk.'showstocklevel', $storeId)):
			
			return Mage::helper('customstockstatus')->__(' * (%s in stock)', $qtyOutput);
		
		else:
			
			return '';
		
		endif;
	}
	
	public function getHasDateExpired($timestamp, $type)
	{
   		if(!$timestamp && $type == 'variable'){ return 'soon'; }
   		if(!$timestamp){ return 'true'; }
   		$date = strtotime($timestamp);
		$today = intval(Mage::getModel('core/date')->timestamp(time()));
		
		if($type == 'preorder'):
			$storeId = Mage::app()->getStore()->getId();
			$dateFormat = Mage::getStoreConfig('custom_stock/general/predateformat', $storeId);
			return $date > $today ? htmlentities(strftime($dateFormat, $date)) : 'true';
		endif;
		if($type == 'expec'):
			$storeId = Mage::app()->getStore()->getId();
			$dateFormat = Mage::getStoreConfig('custom_stock/general/expecdateformat', $storeId);
			if(Mage::getStoreConfig('custom_stock/general/hideexpiredexpec', $storeId))
				return $date > $today ? htmlentities(strftime($dateFormat, $date)) : 'true';
			else
				return htmlentities(strftime($dateFormat, $date));
		endif;
		if($type == 'variable'):
			$storeId = Mage::app()->getStore()->getId();
			$dateFormat = Mage::getStoreConfig('custom_stock/general/expecdateformat', $storeId);
			return htmlentities(strftime($dateFormat, $date));
		endif;
	}
	
	public function cleanStockStatus($theGoods)
	{
		$vars = array('%qty%', '%days%', '%expec%');
		$variable = array();
		$vals = array();
		
		foreach($vars as $var):
			if(strpos($theGoods['stockstatus'], $var)):
				$variable[strpos($theGoods['stockstatus'], $var)] = $var;
				if($var == '%qty%'):
					$vals[strpos($theGoods['stockstatus'], $var)] = $theGoods['qty'];
				elseif($var == '%days%'):
					$vals[strpos($theGoods['stockstatus'], $var)] = Mage::helper('customstockstatus')->getShipDays($theGoods['shipsin'], 'variable');
				elseif($var == '%expec%'):
					$vals[strpos($theGoods['stockstatus'], $var)] = Mage::helper('customstockstatus')->getHasDateExpired($theGoods['expected'], 'variable');
				endif;
			endif;
		endforeach;
		
		return str_replace($variable, $vals, $theGoods['stockstatus']);
	}
		
	public function getAvailabilityText($theGoods, $productkind)
	{
		$origin = Mage::app()->getRequest()->getControllerName();
		$storeId = Mage::app()->getStore()->getId();
		$stockstatus = Mage::helper('customstockstatus')->cleanStockStatus($theGoods);
		$isPreorder = $theGoods['isPreorder'];
		$productId = $theGoods['productId'];
		$qty = $theGoods['qty'];
		$stocked = $theGoods['isInStock'];
		$backordered = $theGoods['backorder'];
		$stockmanaged = $theGoods['managed'];
		$highstock = Mage::getStoreConfig('custom_stock/general/highstock', $storeId);
		$showhighstock = Mage::getStoreConfig('custom_stock/general/showhighstock', $storeId);
		$showstocklevel = Mage::getStoreConfig('custom_stock/'.$productkind.'products/'.$productkind.'showstocklevel', $storeId);
		$s = Mage::getStoreConfig('custom_stock/simpleproducts/limited', $storeId);
		$qtyOutput = ($highstock && $qty > $highstock && $showhighstock ? Mage::helper('customstockstatus')->__('More than %s ', $highstock) : $qty);
		
		if($origin == 'category'):
			$stockstatus = !Mage::getStoreConfig('custom_stock/general/showcustomonlist', $storeId) ? '' : $stockstatus;
			$backordered = $theGoods['config_back'] ? Mage::getStoreConfig('cataloginventory/item_options/backorders', $storeId) : $backordered;
			$stockmanaged = $theGoods['config_stock'] ? Mage::getStoreConfig('cataloginventory/item_options/manage_stock', $storeId) : $stockmanaged;
		endif;
		
		if($stocked == 0):
			
			$expecDate = Mage::helper('customstockstatus')->getHasDateExpired($theGoods['expected'], 'expec');
			
			if(!Mage::getStoreConfig('custom_stock/general/outofstock', $storeId)):
				$txt = '<span class="outofstock">'.Mage::helper('customstockstatus')->__('Out of stock').'</span>';
			elseif(Mage::getStoreConfig('custom_stock/general/outofstock', $storeId) && $stockstatus):
				$txt = '<span class="customstatus">'.$stockstatus.'</span>';
			else:
				$txt = '<span class="outofstock">'.Mage::helper('customstockstatus')->__('Out of stock').'</span>';
			endif;
			
			return $expecDate !== 'true' ? $txt.'<br><span class="expected">'.Mage::helper('customstockstatus')->__('Expected: %s', $expecDate).'</span>' : $txt;
			
		elseif(Mage::helper('customstockstatus')->getHasDateExpired($isPreorder, 'preorder') !== 'true'):
		
			$isPreorder = Mage::helper('customstockstatus')->getHasDateExpired($isPreorder, 'preorder');
			$preorderText = str_replace('%pod%', $isPreorder, $theGoods['preorderText']);
			return $preorderText ? '<span class="preorder">'.$preorderText.'</span>' : '<span class="preorder">'.Mage::helper('customstockstatus')->__('Pre-Order: Available %s', $isPreorder).'</span>';
						
		elseif(Mage::getStoreConfig('custom_stock/'.$productkind.'products/'.$productkind.'customtext', $storeId) && $stockstatus):
			
			return '<span class="customstatus">'.$stockstatus.'</span>';
				
		elseif(($productkind == 'simple' || $productkind == 'virtual') && $qty <= 0 && $stockmanaged == 1):
			
			if($backordered == 1):
				return '<span class="instock">'.Mage::helper('customstockstatus')->__('In stock').'</span>';
			else:
				$expecDate = Mage::helper('customstockstatus')->getHasDateExpired($theGoods['expected'], 'expec');
				$txt = '<span class="backorder">'.Mage::helper('customstockstatus')->__('Backorder').'</span>';
				return $expecDate !== 'true' ? $txt.'<br><span class="expected">'.Mage::helper('customstockstatus')->__('Expected: %s', $expecDate).'</span>' : $txt;
			endif;
				
		elseif(($productkind == 'simple' || $productkind == 'virtual') && $stockmanaged == 1 && $showstocklevel):
		
			return '<span class="instock">'.$qtyOutput.' '.Mage::helper('customstockstatus')->__('in stock').'</span>';
			
		elseif(($productkind == 'simple' || $productkind == 'virtual') && $stockmanaged == 1):
			
			return $qty <= $s ? '<span class="limited">'.Mage::helper('customstockstatus')->__('Limited stock').'</span>' : '<span class="instock">'.Mage::helper('customstockstatus')->__('In stock').'</span>';
			
		else:
		
			return '<span class="instock">'.Mage::helper('customstockstatus')->__('In stock').'</span>';
			
		endif;
	}
	
	public function getShipCountdownText($shipsin)
	{
  		$storeId = Mage::app()->getStore()->getId();
  		$dateFormat = Mage::getStoreConfig('custom_stock/shipoptions/dateformat', $storeId);
  		$localeCode = Mage::getStoreConfig('general/locale/code', $storeId);
		setlocale(LC_TIME, $localeCode);
		date_default_timezone_set(Mage_Core_Model_Locale::DEFAULT_TIMEZONE);
		$holidays = Mage::helper('customstockstatus')->getHolidays();
		$cutoff = Mage::helper('customstockstatus')->getHasCutoffExpired();
		$weekendStart = Mage::getStoreConfig('custom_stock/shipoptions/saturday', $storeId) ? 6 : 5;
		
		$hasExpired = $cutoff[0];
		$the_countdown_date = $cutoff[1];
		
		$today = Mage::getModel('core/date')->timestamp(time());
  		$todayFormated = date('l F j',$today);
  		$dow = date('N', $today);
  		
  		$tomorrow = strtotime($todayFormated.' +1 day');
		$tomorrowFormated = date('l F j',$tomorrow);
		
  		$shipByDate = Mage::helper('customstockstatus')->getShipsByDate($shipsin);
  		$shipByDateFormated = date('l F j',$shipByDate);
  		
  		if($hasExpired === 'true'):
			$the_countdown_date = strtotime('+1 day', $the_countdown_date);
			$dow = date('N', $the_countdown_date);
		endif;
		
		if($holidays === 'false'):
			
			switch($dow):
   				
    			case 6:
    				$the_countdown_date = $weekendStart == 5 ? strtotime('+2 day', $the_countdown_date) : $the_countdown_date;
        		break;
        		
        		case 7:
    				$the_countdown_date = strtotime('+1 day', $the_countdown_date);
        		break;
			
			endswitch;
		
		else:
			
			$m = 0;
			$counterDate = $the_countdown_date;
			
			while((in_array(date('Y-m-d', $counterDate), $holidays) && $m < 20) || ($dow > $weekendStart && $m < 20)):
				$the_countdown_date = strtotime('+1 day', $the_countdown_date);
   				$theDate = date('Y-m-d', $counterDate);
   				$counterDate = strtotime($theDate.' +1 day');
   				$dow = date('N', $counterDate);
				$m++;
			endwhile;
		
		endif;
		
		$difference = $the_countdown_date - $today;
 		//if ($difference < 0) $difference = 0;

		$days_left = floor($difference/60/60/24);
		$hours_left = floor(($difference - $days_left*60*60*24)/60/60);
  		$minutes_left = floor(($difference - $days_left*60*60*24 - $hours_left*60*60)/60);
		
		if($shipByDateFormated == $tomorrowFormated):
			$shipByDate = Mage::helper('customstockstatus')->__('tomorrow');
		elseif($shipByDateFormated == $todayFormated):
			$shipByDate = Mage::helper('customstockstatus')->__('today');
		else:
			$shipByDate = htmlentities(strftime($dateFormat, $shipByDate));
		endif;
			
		if($days_left > 0):
			if($days_left == 1):
				if($hours_left < 2):
					if($hours_left == 1)
						return Mage::helper('customstockstatus')->__('Ships %s if ordered in the next <b>%s day</b>, <b>%s hour</b> and <b>%s minutes</b>!', $shipByDate,$days_left,$hours_left,$minutes_left);
					else
						return Mage::helper('customstockstatus')->__('Ships %s if ordered in the next <b>%s day</b> and <b>%s minutes</b>!', $shipByDate, $days_left, $minutes_left);
				else:
					return Mage::helper('customstockstatus')->__('Ships %s if ordered in the next <b>%s day</b>, <b>%s hours</b> and <b>%s minutes</b>!', $shipByDate, $days_left, $hours_left, $minutes_left);
				endif;
			else:
				if($hours_left < 2):
					if($hours_left == 1)
						return Mage::helper('customstockstatus')->__('Ships %s if ordered in the next <b>%s days</b>, <b>%s hour</b> and <b>%s minutes</b>!', $shipByDate,$days_left,$hours_left,$minutes_left);
					else
						return Mage::helper('customstockstatus')->__('Ships %s if ordered in the next <b>%s days</b> and <b>%s minutes</b>!', $shipByDate, $days_left, $minutes_left);
				else:
					return Mage::helper('customstockstatus')->__('Ships %s if ordered in the next <b>%s days</b>, <b>%s hours</b> and <b>%s minutes</b>!', $shipByDate, $days_left, $hours_left, $minutes_left);
				endif;
			endif;
		else:
			if($hours_left < 2):
				if($hours_left == 1)
					return Mage::helper('customstockstatus')->__('Ships %s if ordered in the next <b>%s hour</b> and <b>%s minutes</b>!', $shipByDate, $hours_left, $minutes_left);
				else
					return Mage::helper('customstockstatus')->__('Ships %s if ordered in the next <b>%s minutes</b>!', $shipByDate, $minutes_left);
			else:
				return Mage::helper('customstockstatus')->__('Ships %s if ordered in the next <b>%s hours</b> and <b>%s minutes</b>!', $shipByDate, $hours_left, $minutes_left);
			endif;
		endif;
	}
	
	public function getShipsByDateMain($businessShipDays, $productkind)
	{
		$storeId = Mage::app()->getStore()->getId();
		$dateFormat = Mage::getStoreConfig('custom_stock/shipoptions/dateformat', $storeId);
		$shipdatetext = Mage::getStoreConfig('custom_stock/'.$productkind.'products/'.$productkind.'shipdatetext', $storeId);
		if($shipdatetext == '') { $shipdatetext = Mage::helper('customstockstatus')->__('Order today, and this will ship by: '); }
		
		$localeCode = Mage::getStoreConfig('general/locale/code', $storeId);
		setlocale(LC_TIME, $localeCode);
		date_default_timezone_set(Mage_Core_Model_Locale::DEFAULT_TIMEZONE);
		
		$today = date('l F j', Mage::getModel('core/date')->timestamp(time()));
		$shipByDate = Mage::helper('customstockstatus')->getShipsByDate($businessShipDays);
		
		return date('l F j', $shipByDate) == $today ? Mage::helper('customstockstatus')->__('%s<b>Today</b>', $shipdatetext) : $shipdatetext.'<b>'.htmlentities(strftime($dateFormat, $shipByDate)).'</b>';
	}
		
	public function getShipDateHtml($theGoods, $productkind)
	{
		$storeId = Mage::app()->getStore()->getId();
		$show = Mage::getStoreConfig('custom_stock/'.$productkind.'products/'.$productkind.'showshipdate', $storeId);
		$style = Mage::getStoreConfig('custom_stock/shipoptions/shipstyle', $storeId);
		$shipsin = Mage::helper('customstockstatus')->getShipDays($theGoods['shipsin'], $productkind);
	
		if(Mage::helper('customstockstatus')->getHasDateExpired($theGoods['isPreorder'], 'preorder') === 'true' && $theGoods['ishidden'] == 0 && $show == 1):
			
			return $style == 1 ? Mage::helper('customstockstatus')->getShipCountdownText($shipsin) : Mage::helper('customstockstatus')->getShipsByDateMain($shipsin, $productkind);
			
		else:
		
			return '';
		
		endif;
	}
	
	public function getShipDays($shipsin, $productkind)
	{
		$storeId = Mage::app()->getStore()->getId();
		$defaultdays = Mage::getStoreConfig('custom_stock/shipoptions/defaultdays', $storeId);
		
		if($productkind == 'variable')
			return $shipsin == '' ? $defaultdays : $shipsin;
		
		if (Mage::getStoreConfig('custom_stock/'.$productkind.'products/'.$productkind.'showshipdate', $storeId) == 1)
			return $shipsin == '' ? $defaultdays : $shipsin;
		
		return '';
	}
	
	public function getRestrictionsText()
	{
		$storeId = Mage::app()->getStore()->getId();
		$showRestrictions = Mage::getStoreConfig('custom_stock/general/restrictions', $storeId);
		$restrictionsText = Mage::getStoreConfig('custom_stock/general/restrictionstext', $storeId);
		$cmsLink = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB).Mage::getStoreConfig('custom_stock/general/cmspage', $storeId);
		
		return $showRestrictions == 1 ? '<br><a href="'.$cmsLink.'" class="restrictions-link">'.$restrictionsText.'</a>' : '';
	}
	
	public function getBackorderAmount()
    {
        return 0;
    }
}