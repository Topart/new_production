<?php

class Springbot_Services_Cmd_Forecast extends Springbot_Services_Abstract
{
	public function run()
	{
		if($storeId = $this->getStoreId()) {
			$harvestId = Mage::helper('combine/harvest')->initRemoteHarvest($storeId);
			self::forecastStore($storeId, $harvestId);
		}
		else {
			self::forecastAllStores();
		}
	}

	public static function forecastAllStores() {
		foreach(Mage::helper('combine/harvest')->getStoresToHarvest() as $store) {
			$harvestId = Mage::helper('combine/harvest')->initRemoteHarvest($store->getStoreId());
			self::forecastStore($store->getStoreId(), $harvestId);
		}
	}

	public static function forecastStore($storeId, $harvestId)
	{
		foreach(Springbot_Services_Cmd_Harvest::getClasses() as $key) {
			$keyUpper = ucwords($key);
			$collection = call_user_func(array('Springbot_Services_Harvest_' . $keyUpper, 'getCollection'), $storeId);
			Mage::helper('combine/harvest')->forecast($collection, $storeId, $keyUpper, $harvestId);
		}
	}

}
