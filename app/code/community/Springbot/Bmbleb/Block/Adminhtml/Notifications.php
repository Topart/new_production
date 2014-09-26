<?php

class Springbot_Bmbleb_Block_Adminhtml_Notifications extends Mage_Adminhtml_Block_Template
{

	/**
	 * Uses PluginStatus helper to determine if major problem needs to be displayed globally
	 */
	public function getMessage()
	{
		if (Mage::getStoreConfig('springbot/config/show_notifications') == 1) {
			if ($problems = Mage::helper('bmbleb/PluginStatus')->getGlobalPluginProblems()) {
				$message = 'Springbot has encountered a small issue. ' .
					'<a href="' . $this->getUrl('bmbleb/adminhtml_problems/index') . '">Click here to get more details</a>. ' .
					'You can turn off Springbot notifications in ' .
					'<a href="' . $this->getUrl('adminhtml/system_config/edit/section/springbot') . '">Springbot configuration.</a>'
				;

				return array('message' => $message, 'type' => 'error');
			}
			else if (Mage::helper('bmbleb/PluginStatus')->needsToLogin()) {
				$message = 'Springbot has been installed successfully. ' .
					'<a href="' . $this->getUrl('bmbleb/adminhtml_index/status') . '">Click here to login</a>. ' .
					'You can turn off Springbot notifications in ' .
					'<a href="' . $this->getUrl('adminhtml/system_config/edit/section/springbot') . '">Springbot configuration.</a>'
				;
				return array('message' => $message, 'type' => 'success');
			}
		}
		return false;
	}
}
