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
    ->addColumn('name', Varien_Db_Ddl_Table::TYPE_TEXT,
        64, array(
            'nullable' => false,
        ), 'Name')
    ->addColumn('seo_information', Varien_Db_Ddl_Table::TYPE_TEXT,
        '64k', array(
            'unsigned' => true,
            'nullable' => false,
        ), 'information notes')
    ->addColumn('short_description', Varien_Db_Ddl_Table::TYPE_TEXT,
        64, array(
            'nullable' => false,
        ), 'short_description')
    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_TEXT,
        32, array(
            'nullable' => false,
            'default' => 'pending',
        ), 'Status')
    ->addColumn('active', Varien_Db_Ddl_Table::TYPE_INTEGER,
        null, array(
            'unsigned' => true,
            'nullable' => false,
        ), 'active')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null, array(
        'nullable' => false,
    ), 'Created at')
    ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        null, array(
        'nullable' => false,
    ), 'Updated at')
    ->setComment('seo informations');
$installer->getConnection()->createTable($table);
$installer->endSetup();

//Image set default values
$model = Mage::getModel('seo/information');
$model->setName('IMAGE');
$model->setSeoInformation('[NAME] by [ARTIST], prints of the artist available on high quality and handmade wall decor, framed art, canvas, poster, giclée and paper.');
$model->setShortDescription('');
$model->setActive(1);
$model->setCreatedAt(strtotime('now'));
$model->setUpdatedAt(strtotime('now'));
$model->save();

//ARTIST set default values
$model = Mage::getModel('seo/information');
$model->setName('ARTIST');
$model->setSeoInformation('Shop our collection of high quality and handmade wall decor, framed art, canvas, poster, giclée and paper by [ARTIST].');
$model->setShortDescription('');
$model->setActive(1);
$model->setCreatedAt(strtotime('now'));
$model->setUpdatedAt(strtotime('now'));
$model->save();

//PRODUCT set default values
$model = Mage::getModel('seo/information');
$model->setName('PRODUCT');
$model->setSeoInformation('Exclusive [NAME] by [ARTIST], prints of the artist available on high quality and handmade canvas, poster, giclée and paper. Exclusive [CATEGORY] art and [STYLE] for you [ROOMS].');
$model->setShortDescription('');
$model->setActive(1);
$model->setCreatedAt(strtotime('now'));
$model->setUpdatedAt(strtotime('now'));
$model->save();

//COLLECTIONS set default values
$model = Mage::getModel('seo/information');
$model->setName('COLLECTIONS');
$model->setSeoInformation('Exclusive Art Collection, featuring [COLLECTION], decorative art and fine art at topart.com. Buy wall decor, canvas art, framed art, prints and posters.');
$model->setShortDescription('');
$model->setActive(1);
$model->setCreatedAt(strtotime('now'));
$model->setUpdatedAt(strtotime('now'));
$model->save();

//SUBJECTS set default values
$model = Mage::getModel('seo/information');
$model->setName('SUBJECTS');
$model->setSeoInformation('Exclusive [SUBJECT] art: high quality and handmade canvas, poster, giclée and paper.');
$model->setShortDescription('');
$model->setActive(1);
$model->setCreatedAt(strtotime('now'));
$model->setUpdatedAt(strtotime('now'));
$model->save();

//CATEGORIES set default values
$model = Mage::getModel('seo/information');
$model->setName('CATEGORIES');
$model->setSeoInformation('Exclusive [CATEGORY] art: high quality and handmade canvas, poster, giclée and paper.');
$model->setShortDescription('');
$model->setActive(1);
$model->setCreatedAt(strtotime('now'));
$model->setUpdatedAt(strtotime('now'));
$model->save();

//ROOMS set default values
$model = Mage::getModel('seo/information');
$model->setName('ROOMS');
$model->setSeoInformation('Exclusive art for your [ROOMS]: high quality and handmade canvas, poster, giclée and paper.');
$model->setShortDescription('');
$model->setActive(1);
$model->setCreatedAt(strtotime('now'));
$model->setUpdatedAt(strtotime('now'));
$model->save();

//STYLE set default values
$model = Mage::getModel('seo/information');
$model->setName('STYLE');
$model->setSeoInformation('Exclusive [STYLE] art: high quality and handmade canvas, poster, giclée and paper.');
$model->setShortDescription('');
$model->setActive(1);
$model->setCreatedAt(strtotime('now'));
$model->setUpdatedAt(strtotime('now'));
$model->save();