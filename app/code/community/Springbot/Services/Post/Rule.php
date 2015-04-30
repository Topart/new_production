<?php

class Springbot_Services_Post_Rule extends Springbot_Services_Post
{
	public function run()
	{
		$api = Mage::getModel('combine/api');
		$collection = new Varien_Data_Collection;
		$harvester = new Springbot_Combine_Model_Harvest_Rules($api, $collection, $this->getDataSource());

		$rule = Mage::getModel('salesrule/rule');
		$rule->load($this->getEntityId());

		// Since rules do not have store ids, we go by the store_id passed from the command line
		$rule->setStoreId($this->getStoreId());

		$harvester->push($rule);
		$harvester->postSegment();
	}
}

