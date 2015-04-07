<?php

class Springbot_BoneCollector_Model_HarvestRule_Observer extends Springbot_BoneCollector_Model_HarvestAbstract
{
	protected $_attributes = array(
		'is_active',
		'name' ,
		'coupon_code',
		'description',
		'conditions',
		'actions',
		'from_date',
		'to_date',
		'uses_per_coupon',
		'uses_per_customer',
		'stop_rules_processing',
		'is_advanced',
		'product_ids',
		'sort_order',
		'simple_action',
		'discount_amount',
		'discount_qty',
		'discount_step',
		'simple_free_shipping',
		'apply_to_shipping',
		'times_used',
		'is_rss',
		'website_ids',
		'customer_group_ids',
	);

	public function onSalesruleRuleSaveAfter($observer)
	{
		try {
			$this->_initObserver($observer);
			$this->_rule = $observer->getEvent()->getRule();

			if ($this->_entityChanged($this->_rule)) {
				$ruleId = $this->_rule->getId();

				foreach ($this->_getWebsiteIds() as $websiteId) {
					if ($website = Mage::app()->getWebsite($websiteId)) {
						foreach ($website->getGroups() as $group) {
							$stores = $group->getStores();
							foreach ($stores as $store) {
								Springbot_Boss::scheduleJob(
									'post:rule',
									array('i' => $ruleId, 's' => $store->getId()),
									Springbot_Services::LISTENER,
									'listener'
								);
							}
						}
					}
				}
			}
		}
		catch (Exception $e) {
			Springbot_Log::error($e);
		}

	}

	public function onSalesruleRuleDeleteBefore($observer)
	{
		try {
			// Runs blocking in session to guarantee record existence
			$rule = $observer->getEvent()->getRule()->getPrimaryCoupon();
			$this->_initObserver($observer);
			Springbot_Boss::scheduleJob(
				'post:rule',
				array('i' => $rule->getId(), 'd' => true),
				Springbot_Services::LISTENER,
				'listener'
			);
		}
		catch (Exception $e) {
			Springbot_Log::error($e);
		}
	}

	protected function _getWebsiteIds()
	{
		$ids = $this->_rule->getWebsiteIds();
		if(is_string($ids)) {
			$ids = explode(',', $ids);
		}
		if(!is_array($ids)) {
			$ids = array();
		}
		return $ids;
	}
}

