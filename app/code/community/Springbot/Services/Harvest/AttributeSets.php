<?php

class Springbot_Services_Harvest_AttributeSets extends Springbot_Services_Harvest
{
	protected $_type = 'attributes';

	public function run()
	{
		$collection = self::getCollection($this->getStoreId())
			->addFieldToFilter('attribute_set_id', array('gt' => $this->getStartId()));
		$stopId = $this->getStopId();
		if ($stopId !== null) {
			$collection->addFieldToFilter('attribute_set_id', array('lteq' => $this->getStopId()));
		}

		$this->_harvester = Mage::getModel('combine/harvest_attributeSets')
			->setDataSource($this->getDataSource())
			->setStoreId($this->getStoreId())
			->setCollection($collection)
			->harvest();

		return parent::run();
	}

	public static function getCollection()
	{
		return Mage::helper('combine/attributes')->getAttributeSets();
	}
}
