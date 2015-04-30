<?php

class Springbot_Services_Harvest_AttributeSets extends Springbot_Services_Harvest
{
	public function run()
	{
		if ($this->getStoreId()) {
			$collection = $this->getCollection($this->getStoreId());
			$api = Mage::getModel('combine/api');
			$harvester =  new Springbot_Combine_Model_Harvest_AttributeSets($api, $collection, $this->getDataSource());
			$harvester->setStoreId($this->getStoreId());
			$harvester->harvest();
			$this->reportCount($harvester);
		}
		else {
			throw new Exception("Missing store id for Attribute Sets harvest");
		}

	}

	public function getCollection($storeId, $partition = null)
	{
		$collection = Mage::helper('combine/attributes')->getAttributeSets();
		//self::getCollection($this->getStoreId())

		$collection->addFieldToFilter('attribute_set_id', array('gt' => $this->getStartId()));

		if ($this->getStopId() !== null) {
			$collection->addFieldToFilter('attribute_set_id', array('lteq' => $this->getStopId()));
		}

		return $collection;
	}

}
