<?php

class Springbot_Combine_Model_Harvest_Coupons extends Springbot_Combine_Model_Harvest
{

	public function getMageModel()
	{
		return 'salesrule/coupon';
	}

	public function getParserModel()
	{
		return 'combine/parser_coupon';
	}

	public function getApiController()
	{
		return 'coupons';
	}

	public function getApiModel()
	{
		return 'coupons';
	}

	public function getRowId()
	{
		return 'coupon_id';
	}

	public function loadMageModel($entityId)
	{
		$model = Mage::getModel($this->getMageModel());
		$model->setStoreId($this->getStoreId());
		$model->load($entityId);
		return $model;
	}
}
