<?php

class Springbot_Services_Post_Product extends Springbot_Services_Post
{
	public function run()
	{
		$api = Mage::getModel('combine/api');
		$collection = new Varien_Data_Collection;
		$harvester = new Springbot_Combine_Model_Harvest_Products($api, $collection, $this->getDataSource());
		$this->_aggregateProduct($this->getEntityId(), $harvester);
		$harvester->postSegment();
	}

	/**
	 * Map store ids to given model
	 *
	 * This method will push all products that are children of the supplied product if they're
	 * a configurable as well as the same product for all stores it is associated to.
	 *
	 * @param integer $entityId
	 */
	protected function _aggregateProduct($entityId, $harvester)
	{
		$product = Mage::getModel('catalog/product')->load($entityId);

		foreach(Mage::helper('combine/harvest')->mapStoreIds($product) as $mapped) {
			$harvester->push($mapped);
		}

		if($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
			Springbot_Log::debug('Executing configurable callback save');
			foreach(Mage::helper('combine/parser')->getChildProductIds($product) as $childId) {
				$this->_aggregateProduct($childId, $harvester);
			}
		}
	}

}
