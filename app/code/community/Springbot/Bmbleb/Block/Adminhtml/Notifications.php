<?php

class Springbot_Bmbleb_Block_Adminhtml_Notifications extends Mage_Adminhtml_Block_Template
{
	/**
	 * Uses PluginStatus helper to determine if major problem needs to be displayed globally
	 */
	public function getMessage()
	{
		try {
			if (Mage::getStoreConfig('springbot/config/show_notifications') == 1) {
				if (Mage::helper('bmbleb/PluginStatus')->needsToLogin()) {
					$message = 'Springbot has been installed successfully. ' .
						'<a href="' . $this->getUrl('bmbleb/adminhtml_index/status') . '">Click here to login</a>. ' .
						'You can turn off Springbot notifications in ' .
						'<a href="' . $this->getUrl('adminhtml/system_config/edit/section/springbot') . '">Springbot configuration.</a>'
					;
					return array('message' => $message, 'type' => 'success');
				}
			}
		}
		catch (Exception $e) {
			Springbot_Log::error($e);
		}
		return false;
	}
}
