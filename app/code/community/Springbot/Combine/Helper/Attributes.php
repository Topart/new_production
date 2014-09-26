<?php

// @TODO test for store id

class Springbot_Combine_Helper_Attributes extends Mage_Core_Helper_Abstract
{
	public function getAttributeSets($type = 'catalog_product')
	{
		$entityTypeId = Mage::getModel('eav/entity')
			->setType($type)
			->getTypeId();
		return Mage::getModel('eav/entity_attribute_set')
			->getCollection()
			->setEntityTypeFilter($entityTypeId);
	}

	public function getCustomerAttributeSet()
	{
		return $this->getCustomerAttributeSets()->getFirstItem();
	}

	public function getCustomerAttributeSets()
	{
		return $this->getAttributeSets('customer');
	}

	public function getAttributeSetById($id)
	{
		return Mage::getModel('eav/entity_attribute_set')->load($id);
	}

	public function getAttributesBySet($attributeSet)
	{
		if(!is_int($attributeSet)) {
			$attributeSet = $this->_resolveSet($attributeSet);
		}
		return Mage::getResourceModel('eav/entity_attribute_collection')
			->setAttributeSetFilter($attributeSet)
			->addSetInfo();
	}

	public function getCustomAttributesBySet($attributeSet)
	{
		$collection = $this->getAttributesBySet($attributeSet);
		return $this->filterNonUserDefined($collection);
	}

	public function getCustomerCustomAttributes($attributeSet)
	{
		$collection = $this->getAttributesBySet($attributeSet);
		return $this->filterSystemForCustomers($collection);
	}

	public function getAttributeGroupsBySet($attributeSet)
	{
		return Mage::getModel('eav/entity_attribute_group')
			->getResourceCollection()
			->setAttributeSetFilter($this->_resolveSet($attributeSet));
	}

	public function getProductAttributesByGroup($group)
	{
		return Mage::getResourceModel('catalog/product_attribute_collection')
			->setAttributeGroupFilter($group->getId())
			->addVisibleFilter()
			->checkConfigurableProducts();
	}

	public function getAllSetsForAttribute($attribute)
	{
		if(is_object($attribute)) {
			$attribute = $attribute->getAttributeId();
		}
		$collection = Mage::getModel('eav/entity_attribute')
			->getCollection()
			->addSetInfo()
			->addFieldToFilter('attribute_id', $attribute);

		if($attribute = $collection->getFirstItem()) {
			$sets = $attribute->getAttributeSetInfo();
			if(is_array($sets)) {
				return array_keys($sets);
			}
		}
	}

	public function getOptionText($attribute, $value)
	{
		foreach($this->getOptionsByAttribute($attribute) as $option) {
			if(isset($option['value']) && $option['value'] == $value) {
				return $option['label'];
			}
		}
	}

	public function getOptionsByAttribute($attribute)
	{
		try {
			if($attribute->usesSource()) {
				return Mage::getResourceModel('eav/entity_attribute_option_collection')
					->setAttributeFilter($attribute->getId())
					->setStoreFilter(0,false)
					->toOptionArray();
			}
		} catch (Exception $e) {
			// onward! We don't stop for poor api design.
		}
	}

	public function getParsedAttributesBySet($set)
	{
		return $this->parseAttributes($this->getCustomAttributesBySet($set));
	}

	public function parseAttributes($_attributes)
	{
		foreach($_attributes as $attr) {
			$toInsert = array(
				'label' => $attr->getFrontendLabel(),
				'attribute_id' => $attr->getAttributeId(),
				'attribute_code' => $attr->getAttributeCode(),
			);

			if($options = $this->getOptionsByAttribute($attr)) {
				$toInsert['options'] = $this->pluckOptions($options);
			}
			$attributes[] = $toInsert;
		}
		return $attributes;
	}

	public function pluckOptions($options)
	{
		return array_values($this->pluck($options, 'label'));
	}

	public function pluck($array, $field)
	{
		$_array = array();
		foreach($array as $item) {
			$_array[] = $item[$field];
		}
		return $_array;
	}

	public function filterNonUserDefined($collection)
	{
		return $collection->addFieldToFilter('is_user_defined', array('gt' => 0));
	}

	public function filterSystemForCustomers($collection)
	{
		$table = Mage::getSingleton('core/resource')->getTableName('customer/eav_attribute');

		$collection->getSelect()->join(
			array('additional_table' => $table),
			'additional_table.attribute_id = main_table.attribute_id',
			'is_system'
		)->where('is_system = 0');

		return $collection;
	}

	protected function _resolveSet($attributeSet)
	{
		if($attributeSet instanceof Mage_Eav_Model_Entity_Attribute_Set) {
			$attributeSet = $attributeSet->getAttributeSetId();
		}
		return $attributeSet;
	}
}
