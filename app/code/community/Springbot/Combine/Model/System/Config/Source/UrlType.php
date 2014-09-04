<?php

class Springbot_Combine_Model_System_Config_Source_UrlType
{
	public function toOptionArray()
	{
		return array(
			array('value' => 'default', 'label'=>Mage::helper('combine')->__('Default')),
			array('value' => 'id_path', 'label'=>Mage::helper('combine')->__('Id Path')),
			array('value' => 'in_store', 'label'=>Mage::helper('combine')->__('In Store')),
		);
	}
}
