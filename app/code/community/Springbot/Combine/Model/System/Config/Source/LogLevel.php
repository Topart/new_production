<?php

class Springbot_Combine_Model_System_Config_Source_LogLevel
{
	public function toOptionArray()
	{
		return array(
			array('value' => '6', 'label'=>Mage::helper('combine')->__('Info')),
			array('value' => '7', 'label'=>Mage::helper('combine')->__('Debug')),
		);
	}
}
