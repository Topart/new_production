<?php

class Springbot_Services_Cmd_Healthcheck extends Springbot_Services_Abstract
{
	public function run()
	{
		// Run checkin process
		$harvestingManager = new Springbot_DataServices_HarvestingManager();
		$harvestingManager->harvestHealthCheck($this->getStoreId());

		// Inspect, rollover and delete logs
		$rollover = new Springbot_Util_Log_Rollover();
		$rollover->expireLogs();
		$rollover->ensureLogSize();
		$rollover->reset();

		Springbot_Log::debug("Healthcheck job complete");
	}
}
