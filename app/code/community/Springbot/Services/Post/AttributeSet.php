<?php

class Springbot_Services_Post_AttributeSet extends Springbot_Services_Post
{
	public function run()
	{
		$api = Mage::getModel('combine/api');
		$collection = new Varien_Data_Collection;
		$harvester = new Springbot_Combine_Model_Harvest_AttributeSets($api, $collection, $this->getDataSource());

		foreach (Mage::helper('combine/harvest')->getStoresToHarvest() as $store) {
			$harvester->setStoreId($store->getStoreId());
			$attributeSet = Mage::getModel('eav/entity_attribute_set')->load($this->getEntityId());
			$harvester->push($attributeSet);
		}
		$harvester->postSegment();
	}

}
