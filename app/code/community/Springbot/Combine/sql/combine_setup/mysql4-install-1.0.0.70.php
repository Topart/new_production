<?php

$installer = $this;
/* @var $installer Springbot_Combine_Model_Resource_Setup */

$installer->startSetup();

try
{
	$installer->getSiteDetails();
	$installer->setDefaultPhpPath();
	if(!Mage::getStoreConfig('springbot/debug/skip_install_log')) {
		$installer->submit();
	}
} catch (Exception $e) {
	Mage::logException($e);
}
$installer->endSetup();
