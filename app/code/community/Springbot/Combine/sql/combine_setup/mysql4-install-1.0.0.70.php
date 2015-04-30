<?php

$installer = $this;
/* @var $installer Springbot_Combine_Model_Resource_Setup */

$installer->startSetup();

try {
	$installer->getSiteDetails();
	if(!Mage::getStoreConfig('springbot/debug/skip_install_log')) {
		$installer->submit();
	}
}
catch (Exception $e) {
	Springbot_Log::error($e);
}
$installer->endSetup();
