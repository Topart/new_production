<?php

class Springbot_Services_Tasks_SetVar extends Springbot_Services
{
	public function run()
	{
		$varName = $this->getVarName();
		$varValue = $this->getVarValue();

		if (!preg_match('/.*\/.*\/.*/', $varName)) {
			$varName = 'springbot/config/' . $varName;
		}

		if (!empty($varName) && ($varName != 'springbot/config/php_exec')) {
			Mage::getModel('core/config')->saveConfig($varName, $varValue, 'default', 0);
		}

		Mage::getConfig()->cleanCache();
		return true;
	}
}
