<?php

class Springbot_Services_Post_Product extends Springbot_Services_Post
{
	protected $_harvester;

	public function run()
	{
		$this->_harvester = Mage::getModel('combine/harvest_products');

		$this->_aggregateProduct($this->getEntityId());

		$this->_harvester->postSegment();
	}

	protected function _aggregateProduct($entityId)
	{
		$product = Mage::getModel('catalog/product')->load($entityId);

		foreach(Mage::helper('combine/harvest')->mapStoreIds($product) as $mapped) {
			$this->_harvester->push($mapped);
		}

		if($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
			Springbot_Log::debug('Executing configurable callback save');
			foreach(Mage::helper('combine/parser')->getChildProductIds($product) as $childId) {
				$this->_aggregateProduct($childId);
			}
		}
	}
}
