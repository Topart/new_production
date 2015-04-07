<?php

class Springbot_Combine_Model_Harvest_AttributeSets extends Springbot_Combine_Model_Harvest
{
	protected $_helper;


	public function getMageModel()
	{
		return 'eav/entity_attribute_set';
	}

	public function getParserModel()
	{
		return 'combine/parser_attributeSet';
	}

	public function getApiController()
	{
		return 'attribute_sets';
	}

	public function getApiModel()
	{
		return 'attribute_sets';
	}

	public function getRowId()
	{
		return 'attribute_set_id';
	}

	/**
	 * Parse caller for dependent parser method
	 *
	 * @param Mage_Core_Model_Abstract $model
	 * @return Zend_Json_Expr
	 */
	public function parse($model)
	{
		$parser = Mage::getModel($this->getParserModel(), $model);
		$parser->setMageStoreId($this->getStoreId());
		$parser->setStoreId($this->getStoreId());
		$parser->parse();

		if ($this->getDelete()) {
			$parser->setIsDeleted(true);
		}
		return $parser->getData();
	}

	public function loadMageModel($id)
	{
		return $this->_getHelper()->getAttributeSetById($id);
	}

	protected function _getHelper()
	{
		if(!isset($this->_helper)) {
			$this->_helper = Mage::helper('combine/attributes');
		}
		return $this->_helper;
	}
}
