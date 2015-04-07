<?php

$installer = $this;
/* @var $installer Springbot_Combine_Model_Resource_Setup */

$installer->startSetup();

$installer->getConnection()->beginTransaction();

$session = Mage::getSingleton('core/session');

try {
    $installer->run("
		CREATE TABLE IF NOT EXISTS `{$installer->getTable('combine/action')}`
		(
			`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			`type` ENUM('atc', 'view', 'purchase') NOT NULL,
			`store_id` INT NOT NULL,
			`visitor_ip` VARCHAR(100) NOT NULL,
			`page_url` TEXT NULL,
			`sku` VARCHAR(255) NOT NULL,
			`sku_fulfillment` VARCHAR(255) NOT NULL,
			`quantity` INT UNSIGNED DEFAULT 1,
			`purchase_id` INT UNSIGNED NULL,
			`quote_id` INT UNSIGNED NULL,
			`category_id` INT UNSIGNED NULL,
			`locked_by` VARCHAR(255) NULL,
			`locked_at` DATETIME NULL,
			`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	");


} catch (Exception $e) {
	Springbot_Log::error('Springbot 1.2.1.0-1.4.0.0 update failed!');
	Springbot_Log::error(new Exception('Install failed clear and retry. ' . $e->getMessage()));
	if (!$session->getSbReinstall()) {
		$session->setSbReinstall(true);
		$installer->reinstallSetupScript('1.2.1.0', '1.4.0.0');
	}
}

try {
	$installer->run("ALTER TABLE `{$installer->getTable('combine/cron_queue')}` ADD COLUMN `next_run_at` DATETIME NULL AFTER `error`;");
} catch (Exception $e) {
	// Don't do anything
}

$installer->endSetup();
