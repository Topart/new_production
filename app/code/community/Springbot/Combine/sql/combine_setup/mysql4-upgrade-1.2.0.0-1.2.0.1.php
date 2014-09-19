<?php

$installer = $this;
/* @var $installer Springbot_Combine_Model_Resource_Setup */

$installer->startSetup();

$installer->getConnection()->beginTransaction();

$installer->run("ALTER TABLE `{$installer->getTable('combine/trackable')}` MODIFY COLUMN `value` varchar(255) NOT NULL;");

$installer->endSetup();

