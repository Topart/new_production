<?php

class Springbot_Services_Harvest_Products extends Springbot_Services_Harvest
{
	protected $_type = 'products';

	public function run()
	{
		$collection = self::getCollection($this->getStoreId())
			->addFieldToFilter('entity_id', array('gt' => $this->getStartId()));
		$stopId = $this->getStopId();
		if ($stopId !== null) {
			$collection->addFieldToFilter('entity_id', array('lteq' => $this->getStopId()));
		}

		$this->_harvester = Mage::getModel('combine/harvest_products')
			->setStoreId($this->getStoreId())
			->setDataSource($this->getDataSource())
			->setCollection($collection)
			->harvest();

		return parent::run();
	}

	public static function getCollection($storeId, $partition = null)
	{
		$collection = Mage::getModel('catalog/product')
			->getCollection()
			->addStoreFilter($storeId);

		if($partition) {
			$collection = parent::limitCollection($collection, $partition);
		}
		return $collection;
	}
}
