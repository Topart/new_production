<?php

class Springbot_Services_Tasks_RegisterStores extends Springbot_Services
{
	public function run()
	{
		$service = new Springbot_Services_Store_Register;
		$helper =  Mage::helper('combine/harvest');
		foreach ($helper->getStoresToHarvest() as $store) {
			$service->setStoreId($store->getStoreId())->run();
		}
		Mage::getConfig()->cleanCache();
		return true;
	}
}




