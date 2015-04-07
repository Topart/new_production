<?php

class Springbot_Services_Post_Guest extends Springbot_Services_Post
{
	public function run()
	{
		$api = Mage::getModel('combine/api');
		$collection = new Varien_Data_Collection;
		$harvester = new Springbot_Combine_Model_Harvest_Guests($api, $collection, $this->getDataSource());
		$harvester->setDelete($this->getDelete());
		$purchase = Mage::getModel('sales/order')->load($this->getStartId());
		$harvester->push($purchase);
		$harvester->postSegment();
	}
}
