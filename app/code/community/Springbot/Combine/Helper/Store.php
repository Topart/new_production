<?php

class Springbot_Combine_Helper_Store extends Mage_Core_Helper_Abstract
{
	protected $_storeId;

	public function setStore($store)
	{
		if($store instanceof Mage_Core_Model_Store) {
			$store = $store->getStoreId();
		}
		$this->_storeId = $store;
		return $this;
	}

	public function getGuid()
	{
		return $this->getValue('springbot/config/store_guid_' . $this->_storeId);
	}

	public function getSpringbotStoreId()
	{
		return $this->getValue('springbot/config/store_id_' . $this->_storeId);
	}

	public function getAccountEmail()
	{
		return $this->getValue('springbot/config/account_email');
	}

	public function getValue($path)
	{
		return Mage::getStoreConfig($path, $this->getStoreId());
	}

	public function getStoreId()
	{
		return $this->_storeId;
	}
}
