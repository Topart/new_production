<?php

class Springbot_Services_Post_Category extends Springbot_Services_Post
{
	public function run()
	{
		$category = Mage::getModel('catalog/category')->load($this->getEntityId());
		$api = Mage::getModel('combine/api');
		$collection = new Varien_Data_Collection;
		$harvester = new Springbot_Combine_Model_Harvest_Categories($api, $collection, $this->getDataSource());

		foreach (Mage::helper('combine/harvest')->mapStoreIds($category) as $mapped) {
			$harvester->push($mapped);
		}

		$harvester->postSegment();
	}
}
