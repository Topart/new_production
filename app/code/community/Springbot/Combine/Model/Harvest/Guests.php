<?php

class Springbot_Combine_Model_Harvest_Guests extends Springbot_Combine_Model_Harvest_Abstract implements Springbot_Combine_Model_Harvester
{
	protected $_mageModel = 'sales/order';
	protected $_parserModel = 'combine/parser_guest';
	protected $_apiController = 'customers';
	protected $_apiModel = 'customers';

	public function loadMageModel($entityId)
	{
		if(!isset($this->_model)) {
			$this->_model = Mage::getModel('sales/order');
		}
		$this->_model->unsetData();
		$this->_model->reset();

		return $this->_model->load($entityId);
	}
}
