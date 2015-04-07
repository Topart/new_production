<?php

class Springbot_Combine_Model_Parser_Guest extends Springbot_Combine_Model_Parser
{
	const TYPE = 'GUEST';
	const OFFSET = 100000000;

	protected $_order;

	public function __construct(Mage_Sales_Model_Order $order)
	{
		$this->_order = $order;
		$this->_address = $this->_order->getShippingAddress();
		$this->_parse();
	}

	protected function _parse()
	{
		if(!is_object($this->_address)) { return false; }

		$this->_data = array(
			'customer_id' => $this->_createCustomerId(),
			'first_name' => $this->_address->getFirstname(),
			'last_name' => $this->_address->getLastname(),
			'email' => $this->_getEmail(),
			'store_id' => $this->_getSpringbotStoreId($this->_order->getStoreId()),
			'has_purchase' => true,
			'json_data' => $this->_getAddressData(),
			'customer_type' => self::TYPE
		);

		return parent::_parse();
	}

	protected function _createCustomerId()
	{
		return self::OFFSET + $this->_order->getEntityId();
	}

	protected function _getAddressData()
	{
		if($address = $this->_order->getBillingAddress()) {
			$class = 'billing';
		} else if ($address = $this->_order->getShippingAddress()) {
			$class = 'shipping';
		} else {
			// No default addresses found
			return;
		}

		return array(
			'street' => $address->getStreet1(),
			'city' => $address->getCity(),
			'state' => $address->getRegionCode(),
			'postal_code' => $address->getPostcode(),
			'country_code' => $address->getCountry(),
			'company' => $address->getCompany(),
			'class' => $class,
			'guest' => true,
		);
	}

	protected function _getEmail()
	{
		$email = $this->_order->getCustomerEmail();

		if($this->hasAddress() && !isset($email)) {
			$email = $this->_address->getEmail();
		}

		if (!$email) {
			if ($quote = Mage::getSingleton('checkout/session')->getQuote()) {
				$email = $quote->getCustomerEmail();
			}
		}

		return $email;
	}

	public function hasAddress()
	{
		return isset($this->_address);
	}
}
