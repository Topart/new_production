<?php

class Springbot_Services_Harvest_Carts extends Springbot_Services_Harvest
{
	public function run()
	{
		if ($this->getStoreId()) {
			$collection = $this->getCollection($this->getStoreId());
			$api = Mage::getModel('combine/api');
			$harvester = new Springbot_Combine_Model_Harvest_Carts($api, $collection, $this->getDataSource());
			$harvester->setStoreId($this->getStoreId());
			$harvester->harvest();
			return $this->reportCount($harvester);
		}
		else {
			throw new exception("Store id missing for carts harvest");
		}
	}

	public function getCollection($storeId, $partition = null)
	{
		$collection = Mage::getModel('sales/quote')->getCollection();
		$collection->addFieldToFilter('customer_email', array('notnull' => true));
		$collection->addFieldToFilter('store_id', $storeId);
		$collection->addFieldToFilter('is_active', 1);

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
