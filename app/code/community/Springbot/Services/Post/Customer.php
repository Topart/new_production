<?php

class Springbot_Services_Post_Customer extends Springbot_Services_Post
{
	public function run()
	{
		$api = Mage::getModel('combine/api');
		$collection = new Varien_Data_Collection;
		$harvester = new Springbot_Combine_Model_Harvest_Customers($api, $collection, $this->getDataSource());
		$harvester->setDelete($this->getDelete());
		$customer = Mage::getModel('customer/customer')->load($this->getStartId());
		$harvester->push($customer);
		$harvester->postSegment();
	}
}
