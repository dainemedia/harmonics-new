<?php

class CJM_CustomStockStatus_Block_System_Config_Holidays extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{
	public function render(Varien_Data_Form_Element_Abstract $element)
	{
       	$middle = '';
       	$holidays = Mage::helper('customstockstatus')->getHolidays();
		$currentYear = date('Y', Mage::getModel('core/date')->timestamp(time()));
		
       	if(Mage::getStoreConfig('custom_stock/shipoptions/enableholidays', Mage::app()->getStore()->getId())):
       		foreach($holidays as $holiday):
       			$holiday = date('M j', strtotime($holiday));
       			$middle .= '&#8226;&nbsp;'.$holiday.'&nbsp;';
       		endforeach;
       	else:
       		$middle = Mage::helper('customstockstatus')->__('Holidays are currently disabled. If wanted, enable below.');
       	endif;
       	
		$html = '<div style="background:url(\'http://chadjmorgan.com/magedev/mage_blank_back.jpg\') scroll #ccc;border:1px solid #CCCCCC;margin-bottom:10px;padding:10px 5px 5px 20px;"><ul><li><h4>';
		$html .= Mage::helper('customstockstatus')->__('%s HOLIDAYS', $currentYear);
		$html .= '</h4><p style="font-size:10px; color:#666666;">'.$middle.'</p></li></ul></div>';
        return $html;
    }
}
