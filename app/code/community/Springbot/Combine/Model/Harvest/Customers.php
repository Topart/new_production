<?php

class Springbot_Combine_Model_Harvest_Customers extends Springbot_Combine_Model_Harvest
{
	public function getMageModel()
	{
		return 'customer/customer';
	}

	public function getParserModel()
	{
		return 'combine/parser_customer';
	}

	public function getApiController()
	{
		return 'customers';
	}

	public function getApiModel()
	{
		return 'customers';
	}


	public function loadMageModel($entityId)
	{
		$model = Mage::getModel('customer/customer');
		return $model->load($entityId);
	}
}
