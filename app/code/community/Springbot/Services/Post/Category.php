<?php

class Springbot_Services_Post_Category extends Springbot_Services_Post
{
	public function run()
	{
		$category = Mage::getModel('catalog/category')->load($this->getEntityId());
		$harvester = Mage::getModel('combine/harvest_categories');

		foreach(Mage::helper('combine/harvest')->mapStoreIds($category) as $mapped) {
			$harvester->push($mapped);
		}

		$harvester->postSegment();
	}
}
