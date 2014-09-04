<?php

class Springbot_Services_Harvest_Subscribers extends Springbot_Services_Harvest
{
	protected $_type = 'subscribers';

	public function run()
	{
		$collection = self::getCollection($this->getStoreId())
			->addFieldToFilter('subscriber_id', array('gt' => $this->getStartId()));
		$stopId = $this->getStopId();
		if ($stopId !== null) {
			$collection->addFieldToFilter('subscriber_id', array('lteq' => $this->getStopId()));
		}

		$this->_harvester = Mage::getModel('combine/harvest_subscribers')
			->setCollection($collection)
			->setDataSource($this->getDataSource())
			->harvest();

		return parent::run();
	}

	public static function getCollection($storeId, $partition = null)
	{
		$collection = Mage::getResourceSingleton('newsletter/subscriber_collection')
			->addFieldToFilter('store_id', $storeId);

		if($partition) {
			$collection = parent::limitCollection($collection, $partition, 'subscriber_id');
		}
		return $collection;
	}
}
