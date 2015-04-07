<?php

class Springbot_Services_Tasks_Jobs extends Springbot_Services
{
	public function run()
	{
		if (!$page = $this->getData('page')) {
			$page = 1;
		}
		$collection = Mage::getModel('combine/cron_queue')->getCollection();
		$collection->setPageSize(20)->setCurPage($page);
		return $collection->toArray();
	}
}




