<?php

class Springbot_Services_Harvest_Carts extends Springbot_Services_Harvest
{
	protected $_type = 'carts';

	public function run()
	{
		$collection = self::getCollection($this->getStoreId())
			->addFieldToFilter('entity_id', array('gt' => $this->getStartId()))
			->addFieldToFilter('entity_id', array('lteq' => $this->getStopId()));

		$this->_harvester = Mage::getModel('combine/harvest_carts')
			->setDataSource($this->getDataSource())
			->setStoreId($this->getStoreId())
			->setCollection($collection)
			->harvest();

		return parent::run();
	}

	public static function getCollection($storeId, $partition = null)
	{
		$collection = Mage::getModel('sales/quote')
			->getCollection()
			->addFieldToFilter('customer_email', array('notnull' => true))
			->addFieldToFilter('store_id', $storeId)
			->addFieldToFilter('is_active', 1);

		if($partition) {
			$collection = parent::limitCollection($collection, $partition);
		}
		return $collection;
	}
}
