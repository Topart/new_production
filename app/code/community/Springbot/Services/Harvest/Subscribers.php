<?php

class Springbot_Services_Harvest_Subscribers extends Springbot_Services_Harvest
{
	public function run()
	{
		$collection = $this->getCollection($this->getStoreId());
		$api = Mage::getModel('combine/api');
		$harvester = new Springbot_Combine_Model_Harvest_Subscribers($api, $collection, $this->getDataSource());
		$harvester->harvest();
		return $this->reportCount($harvester);
	}

	public function getCollection($storeId, $partition = null)
	{
		$collection = Mage::getResourceModel('newsletter/subscriber_collection');
		$collection->addFieldToFilter('store_id', $storeId);

		if ($this->getStartId() !== null) {
			$collection->addFieldToFilter('subscriber_id', array('gt' => $this->getStartId()));
		}
		if ($this->getStopId()) {
			$collection->addFieldToFilter('subscriber_id', array('lteq' => $this->getStopId()));
		}

		if ($partition) {
			$collection = parent::limitCollection($collection, $partition, 'subscriber_id');
		}

		return $collection;
	}
}
