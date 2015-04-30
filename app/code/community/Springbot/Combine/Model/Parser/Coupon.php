<?php

class Springbot_Combine_Model_Parser_Coupon extends Springbot_Combine_Model_Parser
{
	protected $_coupon;
	protected $_accessor = '_coupon';

	public function __construct(Mage_SalesRule_Model_Coupon $coupon)
	{
		$this->_coupon = $coupon;
		$this->_parse();
	}

	protected function _parse()
	{
		$this->setData(array(
			'coupon_id' => $this->_coupon->getCouponId(),
			'store_id' => Mage::helper('combine/harvest')->getSpringbotStoreId($this->_coupon->getStoreId()),
			'rule_id' => $this->_coupon->getRuleId(),
			'code' => $this->_coupon->getCode(),
			'usage_limit' => $this->_coupon->getUsageLimit(),
			'usage_per_customer' => $this->_coupon->getUsagePerCustomer(),
			'times_used' => $this->_coupon->getTimesUsed(),
			'expiration_date' => $this->_coupon->getExpirationDate(),
			'is_primary' => $this->_coupon->getIsPrimary(),
			'type' => $this->_coupon->getType(),
		));

		return parent::_parse();
	}

}
