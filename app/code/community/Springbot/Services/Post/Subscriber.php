<?php

class Springbot_Services_Post_Subscriber extends Springbot_Services_Post
{
	public function run()
	{
		$api = Mage::getModel('combine/api');
		$collection = new Varien_Data_Collection;
		$harvester = new Springbot_Combine_Model_Harvest_Subscribers($api, $collection, $this->getDataSource());
		$harvester->setDelete($this->getDelete());
		$harvester->push(Mage::getModel('newsletter/subscriber')->load($this->getStartId()));
		$harvester->postSegment();
	}
}
