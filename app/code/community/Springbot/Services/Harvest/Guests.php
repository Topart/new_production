<?php

class Springbot_Services_Harvest_Guests extends Springbot_Services_Harvest
{
	protected $_type = 'guests';

	public function run()
	{
		$collection = self::getCollection($this->getStoreId())
			->addFieldToFilter('entity_id', array('gt' => $this->getStartId()));
		$stopId = $this->getStopId();
		if ($stopId !== null) {
			$collection->addFieldToFilter('entity_id', array('lteq' => $this->getStopId()));
		}

		if(method_exists($collection, 'groupByAttribute')) {
			// Magento 1.3.*
			$collection->groupByAttribute('customer_email');
		} else if($collection->getSelect() instanceof Zend_Db_Select) {
			// Deduplicate by customer email
			try {
				$collection->getSelect()->order('increment_id')->group('customer_email');
			} catch (Exception $e) { }
		}

		$this->_harvester = Mage::getModel('combine/harvest_guests')
			->setCollection($collection)
			->setDataSource($this->getDataSource())
			->harvest();

		return parent::run();
	}

	public static function getCollection($storeId, $partition = null)
	{
		$collection = Mage::getModel('sales/order')
			->getCollection()
			->addFieldToFilter('store_id', $storeId)
			->addFieldToFilter('customer_is_guest', true);

		if($partition) {
			$collection = parent::limitCollection($collection, $partition);
		}
		return $collection;
	}
}
