<?php

class Springbot_Shadow_Helper_Listeners extends Mage_Core_Helper_Abstract
{
	public function getListenerIds()
	{
		return explode(',', Mage::getStoreConfig('springbot/config/email_selector'));
	}

	public function getListenerClasses()
	{
		return explode(',', Mage::getStoreConfig('springbot/config/email_selector_classes'));
	}

	public function getBaseUrlNoProtocol()
	{
		return preg_replace('/https?:/','', Mage::getBaseUrl());
	}
}


