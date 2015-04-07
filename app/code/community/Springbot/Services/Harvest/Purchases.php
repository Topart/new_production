<?php

class Springbot_Services_Harvest_Purchases extends Springbot_Services_Harvest
{
	public function run()
	{
		$collection = self::getCollection($this->getStoreId());
		$api = Mage::getModel('combine/api');
		$harvester = new Springbot_Combine_Model_Harvest_Purchases($api, $collection, $this->getDataSource());
		$harvester->harvest();

		return $this->reportCount($harvester);
	}

	public function getCollection($storeId, $partition = null)
	{
		$collection = Mage::getModel('sales/order')->getCollection();

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

		if ($partition) {
			$collection = parent::limitCollection($collection, $partition);
		}
		return $collection;
	}
}
