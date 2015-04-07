<?php

class Springbot_Services_Harvest_Rules extends Springbot_Services_Harvest
{
	public function run()
	{
		$collection = $this->getCollection($this->getStoreId());
		$api = Mage::getModel('combine/api');
		$harvester = new Springbot_Combine_Model_Harvest_Rules($api, $collection, $this->getDataSource());
		$harvester->harvest();

		return $this->reportCount($harvester);
	}

	public function getCollection($storeId, $partition = null)
	{
		$websiteId = Mage::getModel('core/store')->load($storeId)->getWebsiteId();

		// Find all rules for the given storeId
		$collection = Mage::getModel('salesrule/rule')->getCollection();
		$collection->addFieldToFilter('website_ids',
			array(
				array('like' => "%,{$websiteId},%"),
				array('like' => "{$websiteId},%"),
				array('like' => "%,{$websiteId}"),
				array('like' => "{$websiteId}"),
			)
		);

		if ($partition) {
			$collection = parent::limitCollection($collection, $partition, 'rule_id');
		}

		if ($this->getStartId() !== null) {
			$collection->addFieldToFilter('rule_id', array('gt' => $this->getStartId()));
		}

		if ($this->getStopId()) {
			$collection->addFieldToFilter('rule_id', array('lteq' => $this->getStopId()));
		}

		return $collection;
	}
}
