<?php

abstract class Springbot_Combine_Model_Parser extends Varien_Object
{
	protected $_parsed = false;
	protected $_attrProtected = array();

	/*
	 * Public accessor for parse method
	 */
	public function parse()
	{
		$this->_parse();
		return $this;
	}

	/**
	 * Redeclaration of Varien_Object::toJson
	 *
	 * @array $attributes array of attributes to include
	 * @return string
	 */
	public function toJson(array $arrAttributes = array())
	{
		if(!$this->isParsed()) {
			$this->_parse();
		}
		//$this->prune();
		return parent::toJson($arrAttributes);
	}

	public function reinit()
	{
		unset($this->_storeId);
	}

	public function prune()
	{
		$this->_data = $this->_prune($this->_data);
		return $this;
	}

	protected function _prune($array)
	{
		if(is_array($array)) {
			foreach($array as $key => $value) {
				if(is_array($value)) {
					$array[$key] = $this->_prune($value);
				}
				if(empty($value)) {
					unset($array[$key]);
				} else if(is_string($value)) {
					$array[$key] = trim($array[$key]);
				}
			}
		}
		return $array;
	}

	public function getCustomAttributes()
	{
		$return = array();
		$helper = Mage::helper('combine/attributes');
		$model = $this->_getAccessor();
		$attributes = $helper->getAttributesBySet($model->getAttributeSetId());

		foreach($attributes as $attribute) {
			$code = $attribute->getAttributeCode();

			if(!$this->_isProtected($code) && $model->hasData($code)) {
				if($attribute->usesSource()) {
					$value = $helper->getOptionText($attribute, $model->getData($code));
				} else {
					$value = $model->getData($code);
				}

				$return[$code] = $value;
			}
		}

		return $return;
	}

	public function setIsParsed()
	{
		$this->_parsed = true;
		return $this;
	}

	public function isParsed()
	{
		return $this->_parsed;
	}

	public function getAccessibleSku($item)
	{
		if($product = $item->getProduct()) {
			if(
				$product->getVisibility() ==  Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE ||
				$product->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_DISABLED
			) {
				Springbot_Log::debug('Product not visible - attempt to find parent');
				$product = $this->getParentProduct($item);
			}
			return $this->_getHelper()->getTopLevelSku($product);
		}
	}

	protected function _getLandingUrl($product)
	{
		return $this->_getHelper()->getLandingUrl($product);
	}

	protected function _getHelper()
	{
		return Mage::helper('combine/parser');
	}

	protected function _parse()
	{
		return $this->setIsParsed();
	}

	protected function _formatDateTime($date)
	{
		if(!is_null($date)) {
			$_date = new DateTime($date, new DateTimeZone('UTC'));
			return $_date->format(DateTime::ATOM);
		}
	}

	protected function _getAccessor()
	{
		if(!isset($this->_accessor)) {
			throw new Exception('Please set _accessor in Class ' . __CLASS__);
		}
		return $this->{$this->_accessor};
	}

	public function getMageStoreId()
	{
		if(!isset($this->_storeId)) {
			$this->_storeId = $this->_getAccessor()->getStoreId();
		}
		return $this->_storeId;
	}

	public function setMageStoreId($storeId)
	{
		$this->_storeId = $storeId;
		return $this;
	}

	public function getSpringbotStoreId()
	{
		return $this->_getSpringbotStoreId($this->getMageStoreId());
	}

	protected function _getSpringbotStoreId($id)
	{
		return Mage::helper('combine/harvest')->getSpringbotStoreId($id);
	}

	protected function _isProtected($attrCode)
	{
		return in_array($attrCode, $this->_attrProtected);
	}

	protected function _getBaseAmt($field, $model = null)
	{
		if(is_null($model)) {
			$model = $this->_getAccessor();
		}
		return ($amt = $model->getData("base_{$field}")) ? $amt : $model->getData($field);
	}

	protected function _getSkuFailsafe($product)
	{
		// Use array accessor here because getSku method
		// use the type instance's sku (i.e. the child sku)
		if ($sku = $product['sku']) {
			return $sku;
		}
		else {
			return Springbot_Boss::NO_SKU_PREFIX .$product->getEntityId();
		}
	}

}
