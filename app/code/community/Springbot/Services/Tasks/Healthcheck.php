<?php

class Springbot_Services_Tasks_Healthcheck extends Springbot_Services
{
	public function run()
	{
		$healthcheck = new Springbot_Services_Cmd_Healthcheck();
		$healthcheck->setStoreId($this->getStoreId());
		$healthcheck->run();

		Springbot_Log::debug("Scheduling future jobs via endpoint healthcheck");
		Springbot_Boss::scheduleFutureJobs($this->getStoreId());

		return true;
	}
}




