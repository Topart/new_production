<?php

class Springbot_Combine_Model_Parser_Quote extends Springbot_Combine_Model_Parser
{
	protected $_quote;
	protected $_accessor = '_quote';
	protected $_items = array();

	public function __construct(Mage_Sales_Model_Quote $quote)
	{
		$this->_items = array();
		$this->_quote = $quote;
		$this->_parse();
	}

	public function getItemsCount()
	{
		return count($this->_items);
	}

	public function hasCustomerData()
	{
		// Explicitly return true for test suite
		if ($this->_getEmail()) {
			return true;
		}
		else {
			return false;
		}
	}

	protected function _parse()
	{
		$this->setData(array(
			'quote_id' => $this->_quote->getEntityId(),
			'sb_params' => $this->_getSbParams($this->_quote->getEntityId()),
			'store_id' => $this->_getSpringbotStoreId($this->_quote->getStoreId()),
			'customer_id' => $this->_quote->getCustomerId(),
			'quote_created' => $this->_formatDateTime($this->_quote->getCreatedAt()),
			'quote_updated' => $this->_formatDateTime($this->_quote->getUpdatedAt()),
			'quote_converted' => $this->_formatDateTime($this->_quote->getConvertedAt()),
			'customer_email' => $this->_getEmail(),
			'customer_prefix' => $this->_quote->getCustomerPrefix(),
			'customer_firstname' => $this->_quote->getCustomerFirstname(),
			'customer_middlename' => $this->_quote->getCustomerMiddlename(),
			'customer_lastname' => $this->_quote->getCustomerLastname(),
			'customer_suffix' => $this->_quote->getCustomerSuffix(),
			'json_data' => array(
				'checkout_method' => $this->_quote->getCheckoutMethod(),
				'customer_is_guest' => $this->_quote->getCustomerIsGuest(),
				'remote_ip' => $this->_quote->getRemoteIp(),
			),
			'line_items' => $this->_getLineItems(),
		));

		return parent::_parse();
	}

	protected function _getLineItems()
	{
		if($items = $this->_quote->getAllVisibleItems()) {
			foreach($items as $item) {
				$this->_items[] = Mage::getModel('combine/parser_quote_item', $item)->getData();
			}
		}
		return $this->_items;
	}

	public function _getEmail() {
		$email = $this->_quote->getCustomerEmail();
		return $email;
	}

	protected function _getSbParams($quoteId)
	{
		return Mage::helper('combine/trackable')->getTrackablesHashByQuote($quoteId);
	}

}
