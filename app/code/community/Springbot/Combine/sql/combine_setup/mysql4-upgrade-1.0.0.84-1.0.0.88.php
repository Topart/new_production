<?php

$installer = $this;
/* @var $installer Springbot_Combine_Model_Resource_Setup */

$installer->startSetup();

$installer->getConnection()->beginTransaction();

$installer->run("
DELETE FROM `{$installer->getTable('combine/redirect_order')}` WHERE order_id IS NULL;
ALTER TABLE `{$installer->getTable('combine/redirect_order')}` MODIFY COLUMN order_id int(11) NOT NULL;
");

$installer->endSetup();

