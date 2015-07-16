<?php
/**
 * Created by PhpStorm.
 * User: ikis1520
 * Date: 29/06/15
 * Time: 0:26
 */
$installer = $this;
$installer->startSetup();
$table = $installer->getConnection()
    ->newTable($installer->getTable('seo/information'))
    ->addColumn('information_id', Varien_Db_Ddl_Table::
    TYPE_INTEGER, null, array(
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary' => true,
    ), 'information id')
    ->addColumn('created_at', Varien_Db_Ddl_Table::
    TYPE_TIMESTAMP, null, array(
        'nullable' => false,
    ), 'Created at')
    ->addColumn('updated_at', Varien_Db_Ddl_Table::
    TYPE_TIMESTAMP, null, array(
        'nullable' => false,
    ), 'Updated at')->addColumn('firstname', Varien_Db_Ddl_Table::TYPE_TEXT,
        64, array(
            'nullable' => false,
        ), 'First name')
    ->addColumn('lastname', Varien_Db_Ddl_Table::TYPE_TEXT,
        64, array(
            'nullable' => false,
        ), 'Last name')
    ->addColumn('email', Varien_Db_Ddl_Table::TYPE_TEXT,
        64, array(
            'nullable' => false,
        ), 'Email address')
    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_TEXT,
        32, array(
            'nullable' => false,
            'default' => 'pending',
        ), 'Status')
    ->addColumn('message', Varien_Db_Ddl_Table::TYPE_TEXT,
        '64k', array(
            'unsigned' => true,
            'nullable' => false,
        ), 'information notes')
    ->addIndex($installer->getIdxName('seo/information',
            array('email')),
        array('email'))
    ->setComment('seo informations');
$installer->getConnection()->createTable($table);
$installer->endSetup();