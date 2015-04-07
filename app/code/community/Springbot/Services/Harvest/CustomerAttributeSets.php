<?php

class Springbot_Services_Harvest_CustomerAttributeSets extends Springbot_Services_Harvest
{
	public function run()
	{
		$collection = $this->getCollection();
		$api = Mage::getModel('combine/api');
		$harvester = new Springbot_Combine_Model_Harvest_CustomerAttributeSets($api, $collection, $this->getDataSource());
		$harvester->setStoreId($this->getStoreId());
		$harvester->harvest();
		$this->reportCount($harvester);
	}

	public function getCollection()
	{
		return Mage::helper('combine/attributes')->getCustomerAttributeSets();
	}
}
