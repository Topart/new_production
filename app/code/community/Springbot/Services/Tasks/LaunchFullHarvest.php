<?php

class Springbot_Services_Tasks_LaunchFullHarvest extends Springbot_Services
{
	public function run()
	{
		Springbot_Cli::launchHarvest();
		return true;
	}
}
