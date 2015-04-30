<?php

class Springbot_Services_Store_Finalize extends Springbot_Services
{

	public function run()
	{

		$storeId = $this->getStoreId();

		if ($store = Mage::getModel('core/store')->load($storeId)) {
			$helper = Mage::helper('combine/store')->setStore($store);

			Springbot_Log::printLine();
			Springbot_Log::harvest('Store level harvesting complete for Store->'. $storeId .'/'. $helper->getSpringbotStoreId(), true, $storeId);
			Springbot_Log::printLine();

			$api = Mage::getModel('combine/api');
			$api->call('sync_status/' . $helper->getSpringbotStoreId(), '{"status":"synced"}');

			$countResource = Mage::getResourceModel('combine/cron_count');
			$countResource->clearStoreCounts($storeId);

			Mage::getModel('core/config')->saveConfig('springbot/config/harvest_cursor', '0');
		}
	}

}
