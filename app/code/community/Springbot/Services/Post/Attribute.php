<?php

class Springbot_Services_Post_Attribute extends Springbot_Services_Post
{
	public function run()
	{
		$attribute = $this->loadAttribute();
		$harvester = $this->_getAttributeSetHarvester();
		$ids = $this->getAllAttributeSetIds();

		if(($count = count($ids)) > 0) {
			Springbot_Log::debug("{$count} related attribute sets found, saving!");
			foreach($ids as $setId) {
				$set = $this->loadAttributeSet($setId);
				foreach($this->_getStoreIds() as $id) {
					$harvester->setStoreId($id);
					$harvester->push($set);
				}
			}
		} else {
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

	protected function _getAttributeSetHarvester()
	{
		return Mage::getModel('combine/harvest_attributeSets');
	}

	public function loadAttributeSet($setId)
	{
		return Mage::getModel('eav/entity_attribute_set')->load($setId);
	}

	public function getAllAttributeSetIds()
	{
		return Mage::helper('combine/attributes')->getAllSetsForAttribute($this->getEntityId());
	}

	public function loadAttribute()
	{
		return Mage::getModel('eav/entity_attribute')->load($this->getEntityId());
	}
}
