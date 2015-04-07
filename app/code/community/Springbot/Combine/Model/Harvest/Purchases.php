<?php

class Springbot_Combine_Model_Harvest_Purchases extends Springbot_Combine_Model_Harvest
{
	protected $_segmentSize = 100;

	public function getMageModel()
	{
		return 'sales/order';
	}

	public function getParserModel()
	{
		return 'combine/parser_purchase';
	}

	public function getApiController()
	{
		return 'purchases';
	}

	public function getApiModel()
	{
		return 'purchases';
	}

	public function loadMageModel($entityId)
	{
		$model = Mage::getModel($this->getMageModel());
		return $model->load($entityId);
	}
}
