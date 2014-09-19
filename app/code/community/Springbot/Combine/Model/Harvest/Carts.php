<?php

class Springbot_Combine_Model_Harvest_Carts extends Springbot_Combine_Model_Harvest_Abstract implements Springbot_Combine_Model_Harvester
{
	protected $_mageModel = 'sales/quote';
	protected $_parserModel = 'combine/parser_quote';
	protected $_apiController = 'carts';
	protected $_apiModel = 'carts';


	public function loadMageModel($entityId)
	{
		$this->_model = Mage::getModel($this->_getMageModel());
		$this->_model->setStoreId($this->_storeId);
		$this->_model->load($entityId);
		return $this->_model;
	}

	public function parse($model)
	{
		$parser = $this->_getParser($model)->parse($model);
		$parser->setDataSource($this->getDataSource());
		return $parser->getData();
	}


}
