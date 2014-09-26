<?php

class Springbot_Combine_Model_Harvest_AttributeSets extends Springbot_Combine_Model_Harvest_Abstract implements Springbot_Combine_Model_Harvester
{
	protected $_parserModel = 'combine/parser_attributeSet';
	protected $_apiController = 'attribute_sets';
	protected $_apiModel = 'attribute_sets';
	protected $_rowId = 'attribute_set_id';
	protected $_helper;

	public function parse($model)
	{
		$parser = $this->_getParser($model)->setMageStoreId($this->_storeId);
		$parsed = $parser->parse($model);

		if($this->_delete) {
			$parsed->setIsDeleted(true);
		}
		$parsed->setDataSource($this->getDataSource());
		$json = $parsed->toJson();
		return $parsed->getData();
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
