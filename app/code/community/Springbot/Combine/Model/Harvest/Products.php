<?php

class Springbot_Combine_Model_Harvest_Products extends Springbot_Combine_Model_Harvest
{
	protected $_segmentSize = 100;

	public function getMageModel()
	{
		return 'catalog/product';
	}

	public function getParserModel()
	{
		return 'combine/parser_product';
	}

	public function getApiController()
	{
		return 'products';
	}

	public function getApiModel()
	{
		return 'products';
	}


	public function loadMageModel($entityId)
	{
		$model = Mage::getModel($this->getMageModel());
		$model->setStoreId($this->getStoreId());
		$model->load($entityId);
		return $model;
	}
}
