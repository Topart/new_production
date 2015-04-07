<?php

class Springbot_Shadow_Block_Async extends Mage_Core_Block_Template
{
	public function getSpringbotStoreId()
	{
		return Mage::helper('combine/harvest')->getSpringbotStoreId($this->_getStoreId());
	}

	public function getAssetsDomain()
	{
		return Mage::getStoreConfig('springbot/advanced/assets_domain');
	}

	public function getPublicId()
	{
		return Mage::helper('combine')->getPublicGuid($this->_getStoreId());
	}

	private function _getStoreId()
	{
		return Mage::app()->getStore()->getStoreId();
	}
}
