<?php

class Springbot_Combine_Model_Harvest_Subscribers extends Springbot_Combine_Model_Harvest_Abstract implements Springbot_Combine_Model_Harvester
{
	protected $_mageModel = 'newsletter/subscriber';
	protected $_parserModel = 'combine/parser_subscriber';
	protected $_apiController = 'customers';
	protected $_apiModel = 'customers';
	protected $_rowId = 'subscriber_id';

	public function parse($model)
	{
		if($this->_delete) {
			$model->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED);
		}
		return parent::parse($model);
	}
}
