<?php

class Springbot_Combine_Model_Parser_Customer extends Springbot_Combine_Model_Parser
{
	const TYPE = 'REGISTERED';

	protected $_customer;
	protected $_sales;
	protected $_attrProtected = array('password_hash', 'default_billing', 'default_shipping');
	protected $_accessor = '_customer';

	public function __construct(Mage_Customer_Model_Customer $customer)
	{
		$this->_customer = $customer;
		$this->_parse();
	}

	public function getSalesModel()
	{
		if(!isset($this->_sales) || !($this->_sales instanceof Mage_Sales_Model_Order)) {
			$this->_sales = Mage::getModel('sales/order');
		}
		return $this->_sales;
	}

	public function setSalesModel($sales)
	{
		$this->_sales = $sales;
		return $this;
	}

	public function hasCustomerPurchased()
	{
		return $this->_getCustomerOrderCollection()->getSize() > 0;
	}

	protected function _parse()
	{
		$magentoStoreId = $this->_customer->getStore()->getId();
		if ($magentoStoreId == 0) {
			$magentoStoreId = Mage::getStoreConfig('springbot/config/store_zero_alias');
		}
		$this->setData(array(
			'customer_id' => $this->_customer->getEntityId(),
			'first_name' => $this->_customer->getFirstname(),
			'last_name' => $this->_customer->getLastname(),
			'email' => $this->_getEmail(),
			'store_id' => $this->_getSpringbotStoreId($magentoStoreId),
			'has_purchase' => $this->hasCustomerPurchased(),
			'json_data' => $this->_getAddressData(),
			'custom_attribute_set_id' => $this->_customer->getAttributeSetId(),
			'custom_attributes' => $this->getCustomAttributes(),
			'customer_type' => self::TYPE,
		));

		return parent::_parse();
	}

	public function getCustomAttributes()
	{
		$attributes = parent::getCustomAttributes();

		$attributes['group_id'] = $this->_customer->getGroupId();
		$attributes['group'] = $this->_getCustomerGroupName();

		return $attributes;
	}

	protected function _getAddressData()
	{
		if($address = $this->_customer->getDefaultBillingAddress()) {
			$class = 'billing';
		} else if ($address = $this->_customer->getDefaultShippingAddress()) {
			$class = 'shipping';
		} else {
			return new stdClass;
		}

		return array(
			'telephone' => $address->getTelephone(),
			'street' => $address->getStreet1(),
			'city' => $address->getCity(),
			'state' => $address->getRegionCode(),
			'postal_code' => $address->getPostcode(),
			'country_code' => $address->getCountry(),
			'company' => $address->getCompany(),
			'class' => $class,
		);
	}

	protected function _getCustomerOrderCollection()
	{
		return $this->getSalesModel()->getCollection()
			->addAttributeToFilter('customer_id', $this->_customer->getEntityId());
	}

	protected function _getCustomerGroupName()
	{
		$groupId = $this->_customer->getGroupId();
		$group = Mage::getModel('customer/group')->load($groupId);
		return $group->getCode();
	}

	private function _getEmail() {
		$email = $this->_customer->getEmail();
		// If no email is found use the email stored in the session by the javascript listener
		if (!$email) {
			if ($quote = Mage::getSingleton('checkout/session')->getQuote()) {
				$email = $quote->getCustomerEmail();
			}
		}

		return $email;
	}
}
