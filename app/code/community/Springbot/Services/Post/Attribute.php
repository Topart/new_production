<?php

class Springbot_Services_Post_Attribute extends Springbot_Services_Post
{
	public function run()
	{
		$api = Mage::getModel('combine/api');
		$collection = new Varien_Data_Collection;
		$harvester = new Springbot_Combine_Model_Harvest_AttributeSets($api, $collection, $this->getDataSource());

		$ids = Mage::helper('combine/attributes')->getAllSetsForAttribute($this->getEntityId());
		if (($count = count($ids)) > 0) {
			Springbot_Log::debug("{$count} related attribute sets found, saving!");
			foreach ($ids as $setId) {
				$set = Mage::getModel('eav/entity_attribute_set')->load($setId);
				foreach ($this->_getStoreIds() as $id) {
					$harvester->setStoreId($id);
					$harvester->push($set);
				}
			}
		}
		else {
			Springbot_Log::debug("No related attribute sets found");
		}
		$harvester->postSegment();
	}

	protected function _getStoreIds()
	{
		$stores = Mage::helper('combine/harvest')->getStoresToHarvest();
		$ids = array();
		foreach($stores as $store) {
			$ids[] = $store->getStoreId();
		}
		return $ids;
	}

}
