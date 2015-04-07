<?php

class Springbot_Services_Harvest_Products extends Springbot_Services_Harvest
{
	public function run()
	{
		$collection = $this->getCollection($this->getStoreId());

		$api = Mage::getModel('combine/api');
		$harvester = new Springbot_Combine_Model_Harvest_Products($api, $collection, $this->getDataSource());
		$harvester->setStoreId($this->getStoreId());
		$harvester->harvest();

		return $this->reportCount($harvester);
	}

	public function getCollection($storeId, $partition = null)
	{
		$collection = Mage::getModel('catalog/product')
			->getCollection()
			->addStoreFilter($storeId);

		$collection->addFieldToFilter('entity_id', array('gt' => $this->getStartId()));
		$stopId = $this->getStopId();
		if ($stopId !== null) {
			$collection->addFieldToFilter('entity_id', array('lteq' => $this->getStopId()));
		}

		if($partition) {
			$collection = parent::limitCollection($collection, $partition);
		}
		return $collection;
	}
}
