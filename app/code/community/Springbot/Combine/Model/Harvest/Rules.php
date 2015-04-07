<?php

class Springbot_Combine_Model_Harvest_Rules extends Springbot_Combine_Model_Harvest
{
	public function getMageModel()
	{
		return 'salesrule/rule';
	}

	public function getParserModel()
	{
		return 'combine/parser_rule';
	}

	public function getApiController()
	{
		return 'promotions';
	}

	public function getApiModel()
	{
		return 'promotions';
	}

	public function getRowId()
	{
		return 'rule_id';
	}

	public function loadMageModel($entityId)
	{
		$model = Mage::getModel($this->getMageModel());
		$model->setStoreId($this->getStoreId());
		$model->load($entityId);
		return $model;
	}


}
