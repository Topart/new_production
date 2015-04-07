<?php

class Springbot_Services_Tasks_PackageUpdate extends Springbot_Services
{
	public function run()
	{
		$version = $this->getPackageVersion();
		$updater = new Springbot_Services_Cmd_Update();
		$updater->setVersion($version);
		$updater->run();
		return true;
	}
}
