<?php

class Springbot_Services_Harvest_Categories extends Springbot_Services_Harvest
{
	protected $_type = 'categories';

	public function run()
	{
		$collection = self::getCollection($this->getStoreId())
			->addFieldToFilter('entity_id', array('gt' => $this->getStartId()));
		$stopId = $this->getStopId();
		if ($stopId !== null) {
			$collection->addFieldToFilter('entity_id', array('lteq' => $this->getStopId()));
		}

		$this->_harvester = Mage::getModel('combine/harvest_categories')
			->setCollection($collection)
			->setStoreId($this->getStoreId())
			->setDataSource($this->getDataSource())
			->harvest();

		return parent::run();
	}

	public static function getCollection($storeId, $partition = null)
	{
		$rootCategory = Mage::app()->getStore($storeId)->getRootCategoryId();

		$collection = Mage::getModel('catalog/category')
			->getCollection()
			->addAttributeToFilter(array(
				array(
					'attribute' => 'entity_id',
					'eq' => $rootCategory
				),
				array(
					'attribute' => 'path',
					'like' => "1/{$rootCategory}/%"
				),
			));

		if($partition) {
			$collection = parent::limitCollection($collection, $partition);
		}
		return $collection;
	}
}
