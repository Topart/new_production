<?php

class Springbot_Services_Tasks_ResetRetries extends Springbot_Services
{
	public function run()
	{
		$resource = Mage::getResourceModel('combine/cron_queue');
		$resource->resetRetries();
		return true;
	}
}
