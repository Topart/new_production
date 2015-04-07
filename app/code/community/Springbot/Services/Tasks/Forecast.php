<?php

class Springbot_Services_Tasks_Forecast extends Springbot_Services
{
	public function run()
	{
		$instance = new Springbot_Services_Cmd_Forecast();
		$instance->forecastAllStores();
		return true;
	}
}
