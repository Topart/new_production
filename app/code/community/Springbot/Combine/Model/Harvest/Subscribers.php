<?php

class Springbot_Combine_Model_Harvest_Subscribers extends Springbot_Combine_Model_Harvest
{
	public function getMageModel()
	{
		return 'newsletter/subscriber';
	}

	public function getParserModel()
	{
		return 'combine/parser_subscriber';
	}

	public function getApiController()
	{
		return 'customers';
	}

	public function getApiModel()
	{
		return 'customers';
	}

	public function getRowId()
	{
		return 'subscriber_id';
	}

	public function parse($model)
	{
		if ($this->getDelete()) {
			$model->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED);
		}
		return parent::parse($model);
	}
}
