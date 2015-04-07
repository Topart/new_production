<?php

class Springbot_Services_Tasks_ClearStores extends Springbot_Services
{
	public function run()
	{
		foreach (Mage::getStoreConfig('springbot/config') as $configKey => $configValue) {
			if (
				(substr($configKey, 0, strlen('store_id_')) == 'store_id_') ||
				(substr($configKey, 0, strlen('store_guid_')) == 'store_guid_') ||
				(substr($configKey, 0, strlen('security_token_')) == 'security_token_')
			) {
				Mage::getModel('core/config')->saveConfig('springbot/config/' . $configKey, null, 'default', 0);
			}
		}
		Mage::getConfig()->cleanCache();
		return true;
	}
}




