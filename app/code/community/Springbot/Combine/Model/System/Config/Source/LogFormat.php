<?php

class Springbot_Combine_Model_System_Config_Source_LogFormat
{
	public function toOptionArray()
	{
		return array(
			array('value' => 'simple', 'label'=>Mage::helper('combine')->__('Simple')),
			array('value' => 'default', 'label'=>Mage::helper('combine')->__('Default')),
			array('value' => 'expanded', 'label'=>Mage::helper('combine')->__('Expanded')),
		);
	}
}
