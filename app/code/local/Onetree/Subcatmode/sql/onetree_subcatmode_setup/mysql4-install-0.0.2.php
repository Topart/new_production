<?php
$installer = $this;
$installer->startSetup();

$installer->addAttribute('catalog_category', 'collection_image', array(
    'group' => 'General Information',
    'sort_order' => 5,
    'type' => 'varchar',
    'input' => 'image',
    'backend' => 'catalog/category_attribute_backend_image',
    'label' => 'Collection Image',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible' => true,
    'required' => false,
    'user_defined' => true,
    'frontend_input' => '',
    'visible_on_front' => true
));

$installer->endSetup();