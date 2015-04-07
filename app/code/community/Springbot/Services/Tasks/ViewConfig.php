<?php

class Springbot_Services_Tasks_ViewConfig extends Springbot_Services
{
	public function run()
	{
		return Mage::getStoreConfig('springbot');
	}
}




