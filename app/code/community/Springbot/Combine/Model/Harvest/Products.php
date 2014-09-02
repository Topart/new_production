<?php

class Springbot_Combine_Model_Harvest_Products extends Springbot_Combine_Model_Harvest_Abstract implements Springbot_Combine_Model_Harvester
{
	protected $_mageModel = 'catalog/product';
	protected $_parserModel = 'combine/parser_product';
	protected $_apiController = 'products';
	protected $_apiModel = 'products';
	protected $_segmentSize = 100;


	public function loadMageModel($entityId)
	{
		if(!isset($this->_model)) {
			$this->_model = Mage::getModel($this->_getMageModel());
		}
		$this->_model->cleanCache()->reset();
		$this->_model->setStoreId($this->_storeId);
		$this->_model->load($entityId);
		return $this->_model;
	}
}
