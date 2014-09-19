<?php

class Springbot_Services_Log_Installer extends Springbot_Services_Abstract
{
	public function run()
	{
		$setupModel = Mage::getModel('Springbot_Combine_Model_Resource_Setup');

		$setupModel->resendInstallLog();
	}
}
