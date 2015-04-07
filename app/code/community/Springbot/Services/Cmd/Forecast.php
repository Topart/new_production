<?php

class Springbot_Services_Cmd_Forecast extends Springbot_Services
{
	public function run()
	{
		if ($storeId = $this->getStoreId()) {
			$harvestId = Mage::helper('combine/harvest')->initRemoteHarvest($storeId);
			$this->forecastStore($storeId, $harvestId);
		}
		else {
			$this->forecastAllStores();
		}
	}

	public function forecastAllStores() {
		foreach (Mage::helper('combine/harvest')->getStoresToHarvest() as $store) {
			$harvestId = Mage::helper('combine/harvest')->initRemoteHarvest($store->getStoreId());
			$this->forecastStore($store->getStoreId(), $harvestId);
		}
	}

	public function forecastStore($storeId, $harvestId)
	{
		foreach (Springbot_Services_Cmd_Harvest::getClasses() as $key) {
			$keyUpper = ucwords($key);
			$harvestClassName =  'Springbot_Services_Harvest_' . $keyUpper;
			$harvestObject = new $harvestClassName;
			$collection = $harvestObject->getCollection($storeId);
			Mage::helper('combine/harvest')->forecast($collection, $storeId, $keyUpper, $harvestId);
		}
	}

}
