<?php

$installer = $this;

$installer->startSetup();

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

$entityTypeId = $setup->getEntityTypeId('customer');
$attributeSetId = $setup->getDefaultAttributeSetId($entityTypeId);
//$attributeGroupId = $setup->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

$attributeGroupId = 5;

$installer->addAttribute('customer', "twitteraccount", array(
    "type" => "varchar",
    "backend" => "",
    "label" => "Twitter Account",
    "input" => "text",
    "source" => "",
    "visible" => true,
    "required" => false,
    "default" => "",
    "frontend" => "",
    "unique" => false,
    "note" => "Twitter Account"
));

$installer->addAttribute('customer', "pinterestaccount", array(
    "type" => "varchar",
    "backend" => "",
    "label" => "Pinterest Account",
    "input" => "text",
    "source" => "",
    "visible" => true,
    "required" => false,
    "default" => "",
    "frontend" => "",
    "unique" => false,
    "note" => "Pinterest Account"
));

$attributeCodes = array('twitteraccount','pinterestaccount');

foreach($attributeCodes as $attr){
    $attribute = Mage::getSingleton("eav/config")->getAttribute("customer", $attr);

    $someThing = '1010';
    
    $someThing++;
    
    $setup->addAttributeToGroup(
            $entityTypeId, $attributeSetId, $attributeGroupId, $attr, $someThing  //sort_order
    );

    $used_in_forms = array(
        'adminhtml_customer',
        'customer_account_create',
        'customer_account_edit',
        'checkout_register');

    $attribute->setData("used_in_forms", $used_in_forms)
            ->setData("is_used_for_customer_segment", true)
            ->setData("is_system", 0)
            ->setData("is_user_defined", 1)
            ->setData("is_visible", 1)
            ->setData("sort_order", 100);

    $attribute->save();
}