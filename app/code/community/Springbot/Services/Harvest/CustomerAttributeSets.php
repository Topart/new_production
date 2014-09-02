<?php

class Springbot_Services_Harvest_CustomerAttributeSets extends Springbot_Services_Harvest
{
	protected $_type = 'attributes';

	public function run()
	{
		$collection = self::getCollection();

		$this->_harvester = Mage::getModel('combine/harvest_customerAttributeSets')
			->setDataSource($this->getDataSource())
			->setStoreId($this->getStoreId())
			->setCollection($collection)
			->harvest();

		return parent::run();
	}

	public static function getCollection()
	{
		return Mage::helper('combine/attributes')->getCustomerAttributeSets();
	}
}
