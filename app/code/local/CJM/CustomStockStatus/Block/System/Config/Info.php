<?php

class CJM_CustomStockStatus_Block_System_Config_Info extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{
	public function render(Varien_Data_Form_Element_Abstract $element)
	{
        $html = '<div style="background:url(\'http://chadjmorgan.com/magedev/mage_blank_back.jpg\') scroll #ccc;border:1px solid #CCCCCC;margin-bottom:10px;padding:10px 5px 5px 20px;">
		<ul>
			<li>
				<h4>ABOUT SHIPPING MESSAGE STYLES</h4>
				<p style="font-size:10px; color:#666666;">
				COUNTDOWN: Select this option to display a countdown similar to amazon.com. It will display a message similar to this: Ships today if ordered in the next 6 hours and 29 minutes!<br>
				If not offering same day shipping, the word "today" will be replaced with the date the product will ship by based on the processing time setting.<br>
				CUSTOM TEXT AND SHIP DATE: Select this option to display the shipping information like this: Order today, and this will ship by: April 8, 2011. The text before the date appears can<br>
				be set below and the date is based on the processing time setting also below.<br>Shipping messages can be hidden below based on product type or can also be hidden at the product level
				within the custom stock status tab on the product edit page.</p>
				<h4>ABOUT PROCESSING TIME AND CUT-OFF</h4>
				<p style="font-size:10px; color:#666666;">
				PROCESSING CUT-OFF TIME: This setting is the time in which orders are no longer processed that day. If a customer orders before this time is reached, the current day will count as a<br>
				processing day. If the customer orders after this time, processing will not start until tomorrow. Time is based on Magento installations time zone setting.<br>
				DEFAULT ORDER PROCESSING TIME: This setting is the default number of days to process an order. This setting can also be set in the products custom stock status tab. If set within<br>
				the product, the products value overrides the default value. If offering same day shipping if ordered by the cut-off time, enter 1 for the value.
				</p>
				</li></ul></div>';
        
        return $html;
    }
}
