<?php

class Springbot_Services_Tasks_Run extends Springbot_Services
{
	public function run()
	{
		$cronWorker = Mage::getModel('combine/cron_worker');
		$cronWorker->run();

		$helper = Mage::helper('shadow/prattler');
		return $helper->getPrattlerResponse();
	}
}




