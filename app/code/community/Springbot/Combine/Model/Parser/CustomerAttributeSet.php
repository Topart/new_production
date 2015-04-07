<?php

class Springbot_Combine_Model_Parser_CustomerAttributeSet extends Springbot_Combine_Model_Parser_AttributeSet
{
	protected $_type = 'customer';

	protected function _parseAttributes()
	{
		$helper = $this->_getHelper();
		$attributes = $helper->parseAttributes($helper->getCustomerCustomAttributes($this->_set));

		$customerGroupModel = new Mage_Customer_Model_Group();
		$customerGroups  = $customerGroupModel->getCollection()->toOptionHash();
		$optionsArray = array();
		foreach ($customerGroups as $key => $customerGroup){
			$optionsArray[] = $customerGroup;
		}

		$attributes[] = array(
			'label' => 'Group',
			'attribute_id' => 9000000,
			'attribute_code' => 'group',
			'options' => $optionsArray
		);

		return $attributes;

	}
}
