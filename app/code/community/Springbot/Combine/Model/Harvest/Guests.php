<?php

class Springbot_Combine_Model_Harvest_Guests extends Springbot_Combine_Model_Harvest
{
	public function getMageModel()
	{
		return 'sales/order';
	}

	public function getParserModel()
	{
		return 'combine/parser_guest';
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
		$model = Mage::getModel('sales/order');
		return $model->load($entityId);
	}
}
