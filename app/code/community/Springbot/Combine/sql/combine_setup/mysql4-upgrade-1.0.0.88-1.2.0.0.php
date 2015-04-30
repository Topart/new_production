<?php

$installer = $this;
/* @var $installer Springbot_Combine_Model_Resource_Setup */

$installer->startSetup();

$installer->getConnection()->beginTransaction();

$session = Mage::getSingleton('core/session');

try {
	$installer->run("
	CREATE TABLE IF NOT EXISTS `{$installer->getTable('combine/trackable')}`
	(
		`id` INT(11) unsigned NOT NULL auto_increment,
		`email` VARCHAR(255) NOT NULL,
		`type` VARCHAR(255) NOT NULL,
		`value` CHAR(24) NOT NULL,
		`quote_id` INT(11) DEFAULT NULL,
		`order_id` INT(11) DEFAULT NULL,
		`customer_id` INT(11) DEFAULT NULL,
		`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (`id`),
		KEY `IDX_EMAIL` (`email`),
		KEY `IDX_QUOTE_ID` (`quote_id`),
		KEY `IDX_ORDER_ID` (`order_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");
} catch (Exception $e) {
	Springbot_Log::error(new Exception('Install failed clear and retry'));
	if (!$session->getSbReinstall()) {
		$session->setSbReinstall(true);
		$installer->reinstallSetupScript('1.0.0.70', '1.2.0.0');
	}
}

$session->setSbReinstall(false);
$installer->endSetup();
