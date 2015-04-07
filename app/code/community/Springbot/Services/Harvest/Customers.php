<?php

class Springbot_Services_Harvest_Customers extends Springbot_Services_Harvest
{
	public function run()
	{
		$api = Mage::getModel('combine/api');
		$collection = $this->getCollection($this->getStoreId());
		$harvester = new Springbot_Combine_Model_Harvest_Customers($api, $collection, $this->getDataSource());
		$harvester->harvest();
		return $this->reportCount($harvester);
	}

	public function getCollection($storeId, $partition = null)
	{
		$collection = Mage::getModel('customer/customer')->getCollection();

		if ($storeId == Mage::getStoreConfig('springbot/config/store_zero_alias')) {
			$collection->addFieldToFilter('store_id',
				array(
					array('eq' => 0),
					array('eq' => $storeId),
				)
			);
		}
		else {
			$collection->addFieldToFilter('store_id', $storeId);
		}


		if ($this->getStartId() !== null) {
			$collection->addFieldToFilter('entity_id', array('gt' => $this->getStartId()));
		}

		if ($this->getStopId()) {
			$collection->addFieldToFilter('entity_id', array('lteq' => $this->getStopId()));
		}

		if($partition) {
			$collection = parent::limitCollection($collection, $partition);
		}
		return $collection;
	}
}
