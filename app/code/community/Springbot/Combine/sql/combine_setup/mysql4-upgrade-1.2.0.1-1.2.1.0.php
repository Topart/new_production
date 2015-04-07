<?php

$installer = $this;
/* @var $installer Springbot_Combine_Model_Resource_Setup */

$installer->startSetup();

$installer->getConnection()->beginTransaction();

try {
$installer->run("
	CREATE TABLE IF NOT EXISTS `{$installer->getTable('combine/cron_queue')}`
	(
		`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
		`method` VARCHAR(255) NOT NULL,
		`args` TEXT NOT NULL,
		`store_id` INT NOT NULL,
		`command_hash` CHAR(40) NOT NULL,
		`queue` VARCHAR(255) NOT NULL DEFAULT 'default',
		`priority` INT UNSIGNED NOT NULL DEFAULT 5,
		`attempts` INT UNSIGNED NOT NULL DEFAULT 0,
		`run_at` DATETIME NULL,
		`locked_at` DATETIME NULL,
		`locked_by` VARCHAR(255) NULL,
		`error` TEXT NULL,
		`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		 UNIQUE (`command_hash`),
		PRIMARY KEY (`id`),
		KEY `IDX_PRIORITY_CREATED_AT` (`priority`, `created_at`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	CREATE TABLE IF NOT EXISTS `{$installer->getTable('combine/cron_count')}`
	(
		`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
		`entity` VARCHAR(255) NOT NULL,
		`store_id` INT NOT NULL,
		`harvest_id` CHAR(40) NOT NULL,
		`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		`completed` TIMESTAMP NULL DEFAULT NULL,
		`count` INT UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");
} catch (Exception $e) {
	Springbot_Log::error('Springbot 1.2.0.0-1.2.1.0 update failed!');
}

$installer->endSetup();
