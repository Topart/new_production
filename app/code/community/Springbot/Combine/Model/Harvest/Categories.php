<?php

class Springbot_Combine_Model_Harvest_Categories extends Springbot_Combine_Model_Harvest_Abstract implements Springbot_Combine_Model_Harvester
{
	protected $_mageModel = 'catalog/category';
	protected $_parserModel = 'combine/parser_category';
	protected $_apiController = 'categories';
	protected $_apiModel = 'categories';

	/**
	 * Parse caller for dependent parser method
	 *
	 * @param Mage_Core_Model_Abstract $model
	 * @return Zend_Json_Expr
	 */
	public function parse($model)
	{
		$parsed = $this->_getParser($model)
			->setMageStoreId($this->_storeId)
			->parse($model);

		if($this->_delete) {
			$parsed->setIsDeleted(true);
		}
		$parsed->setDataSource($this->getDataSource());
		$json = $parsed->toJson();
		return $parsed->getData();
	}

	public function loadMageModel($entityId)
	{
		if(!isset($this->_model)) {
			$this->_model = Mage::getModel($this->_getMageModel());
		}
		$this->_model->setStoreId($this->_storeId);
		$this->_model->load($entityId);
		return $this->_model;
	}
}
