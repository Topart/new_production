<?php

class Springbot_Combine_Model_Harvest_Categories extends Springbot_Combine_Model_Harvest
{

	public function getMageModel()
	{
		return 'catalog/category';
	}

	public function getParserModel()
	{
		return 'combine/parser_category';
	}

	public function getApiController()
	{
		return 'categories';
	}

	public function getApiModel()
	{
		return 'categories';
	}


	public function loadMageModel($entityId)
	{
		$model = Mage::getModel($this->getMageModel());
		$model->setStoreId($this->getStoreId());
		$model->load($entityId);
		return $model;
	}
}
