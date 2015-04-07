<?php

class Springbot_Services_Tasks_ClearJobs extends Springbot_Services
{
	public function run()
	{
		$resource = Mage::getResourceModel('combine/cron_queue');
		$resource->removeHarvestRows(null, false);
		return true;
	}
}




