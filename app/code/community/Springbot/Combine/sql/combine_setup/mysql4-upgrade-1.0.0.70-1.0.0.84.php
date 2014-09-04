<?php

$installer = $this;
/* @var $installer Springbot_Combine_Model_Resource_Setup */

$installer->startSetup();

$installer->getConnection()->beginTransaction();

$installer->run("
	CREATE TABLE IF NOT EXISTS `{$installer->getTable('combine/redirect')}`
	(
		`id` INT(11) unsigned NOT NULL auto_increment,
		`email` VARCHAR(255) NOT NULL,
		`redirect_id` CHAR(24) NOT NULL,
		`quote_id` INT(11) DEFAULT NULL,
		`customer_id` INT(11) DEFAULT NULL,
		`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (`id`),
		UNIQUE KEY `UNQ_COMPOUND_EMAIL_REDIRECT_ID` (`email`, `redirect_id`),
		KEY `IDX_EMAIL` (`email`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->run("
	CREATE TABLE IF NOT EXISTS `{$installer->getTable('combine/redirect_order')}`
	(
		`id` INT(11) unsigned NOT NULL auto_increment,
		`redirect_entity_id` INT(11) NOT NULL,
		`order_id` INT(11) DEFAULT NULL,
		`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY (`id`),
		UNIQUE KEY `UNQ_COMPOUND_REDIRECT_ENTITY_ORDER_ID` (`redirect_entity_id`, `order_id`),
		KEY `IDX_REDIRECT_ENTITY_ID` (`redirect_entity_id`),
		KEY `IDX_ORDER_ID` (`order_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();
