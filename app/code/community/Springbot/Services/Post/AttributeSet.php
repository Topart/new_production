<?php

class Springbot_Services_Post_AttributeSet extends Springbot_Services_Post
{
	public function run()
	{
		$harvester = Mage::getModel('combine/harvest_attributeSets');

		foreach($this->_getStoreIds() as $id) {
			$harvester->setStoreId($id);
			$harvester->push(Mage::getModel('eav/entity_attribute_set')->load($this->getEntityId()));
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
