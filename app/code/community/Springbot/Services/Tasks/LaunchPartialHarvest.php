<?php

class Springbot_Services_Tasks_LaunchPartialHarvest extends Springbot_Services
{
	public function run()
	{
		Mage::helper('combine/harvest')->truncateEngineLogs();
		Springbot_Boss::scheduleJob(
			'cmd:harvest',
			array(
				's' => $this->getStoreId(),
				'c' => $this->getType(),
			),
			Springbot_Services::HARVEST,
			'default',
			$this->getStoreId()
		);
		return true;
	}
}
