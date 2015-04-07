<?php

class Springbot_Services_Harvest_Categories extends Springbot_Services_Harvest
{
	public function run()
	{
		if ($this->getStoreId()) {
			$collection = $this->getCollection($this->getStoreId());
			$api = Mage::getModel('combine/api');
			$harvester = new Springbot_Combine_Model_Harvest_Categories($api, $collection, $this->getDataSource());
			$harvester->setStoreId($this->getStoreId());
			$harvester->harvest();
			return $this->reportCount($harvester);
		}
		else {
			throw new Exception('Store id missing for category harvest');
		}
	}

	public function getCollection($storeId, $partition = null)
	{
		$rootCategory = Mage::app()->getStore($storeId)->getRootCategoryId();
		$collection = Mage::getModel('catalog/category')->getCollection();
		if ($this->getStopId() !== null) {
			$collection->addFieldToFilter('entity_id', array('lteq' => $this->getStopId()));
		}
		if ($this->getStartId() !== null) {
			$collection->addFieldToFilter('entity_id', array('gt' => $this->getStartId()));
		}
		$collection->addAttributeToFilter(
			array(
				array(
					'attribute' => 'entity_id',
					'eq' => $rootCategory
				),
				array(
					'attribute' => 'path',
					'like' => "1/{$rootCategory}/%"
				),
			)
		);

		if ($partition) {
			$collection = parent::limitCollection($collection, $partition);
		}

		return $collection;
	}
}
