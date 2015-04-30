<?php

class Springbot_Services_Tasks_UnlockJobs extends Springbot_Services
{
	public function run()
	{
		$resource = Mage::getResourceModel('combine/cron_queue');
		$resource->unlockOrphanedRows();
		return true;
	}
}




