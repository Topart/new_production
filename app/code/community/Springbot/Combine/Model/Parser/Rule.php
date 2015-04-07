<?php

class Springbot_Combine_Model_Parser_Rule extends Springbot_Combine_Model_Parser
{
	protected $_rule;
	protected $_accessor = '_rule';

	public function __construct(Mage_SalesRule_Model_Rule $rule)
	{
		$this->_rule = $rule;
		$this->_parse();
	}

	protected function _parse()
	{
		$this->setData(array(
			'rule_id' => $this->_rule->getId(),
			'store_id' => Mage::helper('combine/harvest')->getSpringbotStoreId($this->_rule->getStoreId()),
			'is_active' => $this->_rule->getIsActive(),
			'name' => $this->_rule->getName(),
			'coupon_code' => $this->_rule->getCouponCode(),
			'description' => $this->_rule->getDescription(),
			'conditions' => $this->_serializedToJson($this->_rule->getConditionsSerialized()),
			'actions' => $this->_serializedToJson($this->_rule->getActionsSerialized()),
			'from_date' => $this->_rule->getFromDate(),
			'to_date' => $this->_rule->getToDate(),
			'uses_per_coupon' => $this->_rule->getUsesPerCoupon(),
			'uses_per_customer' => $this->_rule->getUsesPerCustomer(),
			'stop_rules_processing' => $this->_rule->getUsesPerCustomer(),
			'is_advanced' => $this->_rule->getUsesPerCustomer(),
			'product_ids' => $this->_rule->getProductIds(),
			'sort_order' => $this->_rule->getSortOrder(),
			'simple_action' => $this->_rule->getSimpleAction(),
			'discount_amount' => $this->_rule->getDiscountAmount(),
			'discount_qty' => $this->_rule->getDiscountQty(),
			'discount_step' => $this->_rule->getDiscountStep(),
			'simple_free_shipping' => $this->_rule->getSimpleFreeShipping(),
			'apply_to_shipping' => $this->_rule->getApplyToShipping(),
			'times_used' => $this->_rule->getTimesUsed(),
			'is_rss' => $this->_rule->getIsRss(),
			'website_ids' => $this->_rule->getWebsiteIds(),
			'customer_group_ids' => $this->_rule->getCustomerGroupIds(),
		));

		return parent::_parse();
	}

	protected function _serializedToJson($arg)
	{
		return unserialize($arg);
	}
}
