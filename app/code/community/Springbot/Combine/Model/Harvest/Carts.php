<?php

class Springbot_Combine_Model_Harvest_Carts extends Springbot_Combine_Model_Harvest
{
	public function getMageModel()
	{
		return 'sales/quote';
	}

	public function getParserModel()
	{
		return 'combine/parser_quote';
	}

	public function getApiController()
	{
		return 'carts';
	}

	public function getApiModel()
	{
		return 'carts';
	}


	public function loadMageModel($entityId)
	{
		$model = Mage::getModel($this->getMageModel());
		$model->setStoreId($this->getStoreId());
		$model->load($entityId);
		return $model;
	}



}
