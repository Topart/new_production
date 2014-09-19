<?php

class Springbot_Services_Harvest_Customers extends Springbot_Services_Harvest
{
	protected $_type = 'customers';

	public function run()
	{
		$collection = self::getCollection($this->getStoreId())
			->addFieldToFilter('entity_id', array('gt' => $this->getStartId()));
		$stopId = $this->getStopId();
		if ($stopId !== null) {
			$collection->addFieldToFilter('entity_id', array('lteq' => $this->getStopId()));
		}
		$this->_harvester = Mage::getModel('combine/harvest_customers')
			->setCollection($collection)
			->setDataSource($this->getDataSource())
			->harvest();

		return parent::run();
	}

	public static function getCollection($storeId, $partition = null)
	{
		$collection = Mage::getModel('customer/customer')
			->getCollection()
			->addFieldToFilter('store_id', $storeId);

		if($partition) {
			$collection = parent::limitCollection($collection, $partition);
		}
		return $collection;
	}
}
