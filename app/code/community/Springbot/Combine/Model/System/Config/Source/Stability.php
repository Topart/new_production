<?php

class Springbot_Combine_Model_System_Config_Source_Stability
{
	public function toOptionArray()
	{
		return array(
			array('value' => 'stable', 'label'=>Mage::helper('combine')->__('Stable')),
			array('value' => 'beta', 'label'=>Mage::helper('combine')->__('Beta')),
			//array('value' => 'alpha', 'label'=>Mage::helper('combine')->__('Alpha')),
		);
	}
}
