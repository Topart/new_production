<?php

class Springbot_Services_Tasks_ResumeHarvest extends Springbot_Services
{
	public function run()
	{
		Mage::getConfig()->cleanCache();
		Mage::getConfig()->reinit();
		Springbot_Cli::resumeHarvest();
		return true;
	}
}
