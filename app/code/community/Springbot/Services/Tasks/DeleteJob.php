<?php

class Springbot_Services_Tasks_DeleteJob extends Springbot_Services
{
	public function run()
	{
		if (is_numeric($this->getJobId())) {
			$resource = Mage::getModel('combine/cron_queue')->getResource();
			$resource->removeHarvestRow($this->getJobId());
			return true;
		}
		else {
			return false;
		}
	}
}
