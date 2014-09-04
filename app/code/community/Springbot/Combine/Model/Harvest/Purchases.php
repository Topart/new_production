<?php

class Springbot_Combine_Model_Harvest_Purchases extends Springbot_Combine_Model_Harvest_Abstract implements Springbot_Combine_Model_Harvester
{
	protected $_mageModel = 'sales/order';
	protected $_parserModel = 'combine/parser_purchase';
	protected $_apiController = 'purchases';
	protected $_apiModel = 'purchases';
	protected $_segmentSize = 100;

	public function loadMageModel($entityId)
	{
		if(!isset($this->_model)) {
			$this->_model = Mage::getModel($this->_getMageModel());
		}
		$this->_model->unsetData();
		$this->_model->reset();
		return $this->_model->load($entityId);
	}
}
