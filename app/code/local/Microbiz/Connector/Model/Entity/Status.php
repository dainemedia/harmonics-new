<?php
class Microbiz_Connector_Model_Entity_Status extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
	public function getAllOptions()
	{
		if ($this->_options === null) {
			$this->_options = array();			
			$this->_options[] = array(
                    'value' => 1,
                    'label' => 'Enabled'
			);
			$this->_options[] = array(
                    'value' => 2,
                    'label' => 'Disabled'
			);	
		}

		return $this->_options;
	}
}
