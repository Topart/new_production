<?php

class Springbot_Combine_Model_Harvest_Customers extends Springbot_Combine_Model_Harvest_Abstract implements Springbot_Combine_Model_Harvester
{
	protected $_mageModel = 'customer/customer';
	protected $_parserModel = 'combine/parser_customer';
	protected $_apiController = 'customers';
	protected $_apiModel = 'customers';

	public function loadMageModel($entityId)
	{
		if(!isset($this->_model)) {
			$this->_model = Mage::getModel('customer/customer');
		}
		// This unsets addresses so we can reuse this model
		$this->_model->cleanAllAddresses();
		return $this->_model->load($entityId);
	}
}
