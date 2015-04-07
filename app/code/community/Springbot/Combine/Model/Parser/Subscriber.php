<?php

class Springbot_Combine_Model_Parser_Subscriber extends Springbot_Combine_Model_Parser
{
	const TYPE = 'SUBSCRIBER';
	const SUBSCRIBER_MODE = 'NL';

	protected $_subscriber;

	public function __construct(Mage_Newsletter_Model_Subscriber $subscriber)
	{
		$this->_subscriber = $subscriber;
		$this->_parse();
	}

	public function isCustomer()
	{
		$customerId = $this->_subscriber->getCustomerId();
		return !empty($customerId);
	}

	protected function _parse()
	{
		$this->setCustomerId($this->_buildCustomerId())
			->setSubscriberId($this->_subscriber->getSubscriberId())
			->setStoreId($this->_getSpringbotStoreId($this->_subscriber->getStoreId()))
			->setEmail($this->_subscriber->getSubscriberEmail())
			->setOptinStatus($this->_subscriber->getSubscriberStatus())
			->setSubscriberMode(self::SUBSCRIBER_MODE)
			->setCustomerType(self::TYPE);
		return parent::_parse();
	}

	protected function _buildCustomerId()
	{
		$customerId = $this->_subscriber->getCustomerId();

		if(empty($customerId)) {
			$id = $this->_subscriber->getSubscriberId();
			$customerId = (int) str_pad($id, 9, '9', STR_PAD_LEFT);
		}
		return $customerId;
	}
}
