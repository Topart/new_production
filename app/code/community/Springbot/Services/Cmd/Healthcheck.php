<?php

class Springbot_Services_Cmd_Healthcheck extends Springbot_Services
{
	const SUCCESSFUL_RESPONSE = 'ok';

	public function run()
	{
		// Run checkin process
		$this->healthcheck($this->getStoreId());

		// Scrape coupons
		if(Mage::getStoreConfig('springbot/advanced/scrape_coupons')) {
			$this->_scrapeEntities();
		}

		// Inspect, rollover and delete logs
		$rollover = new Springbot_Util_Log_Rollover();
		$rollover->expireLogs();
		$rollover->ensureLogSize();
		$rollover->reset();

		// Clean orphaned jobs
		$cleanup = new Springbot_Services_Work_Cleanup();
		$cleanup->run();

		Springbot_Log::debug("Healthcheck job complete");
	}

	public function healthcheck($storeId)
	{
		Springbot_Log::debug("Running healthcheck for $storeId");

		if ($storeId) {
			$springbotStoreId = Mage::helper('combine/harvest')->getSpringbotStoreId($storeId);
			$packageVersion = Mage::getConfig()->getModuleConfig("Springbot_Combine")->version;

			$result = Mage::getModel('combine/api')
				->call(
					'harvest_master',
					'{"store_id":"'.$springbotStoreId.'","version":"'. $packageVersion .'"}'
				);

			if (isset($result['status']) && $result['status'] == self::SUCCESSFUL_RESPONSE) {
				foreach ($result['commands'] as $cmd) {
					$task = $this->_makeTaskInstance($cmd);
					$task->run();
				}
			}
		}
	}

	public function doFinally()
	{
		Springbot_Log::debug("Scheduling future jobs from healthcheck job");
		Springbot_Boss::scheduleFutureJobs($this->getStoreId());
	}

	private function _scrapeEntities()
	{
		$lastPostedCouponId = Mage::getStoreConfig('springbot/tmp/last_coupon_id');
		if (!$lastPostedCouponId) {
			$lastPostedCouponId = 0;
		}
		$couponsToPost = Mage::getModel('salesrule/coupon')->getCollection()
			->addFieldToFilter('coupon_id', array('gt' => $lastPostedCouponId));

		$couponsToPost->getSelect()->order('coupon_id', 'ASC');
		$lastFoundCouponId = null;
		foreach ($couponsToPost as $couponToPost) {
			Springbot_Boss::scheduleJob('post:coupon', array('i' => $couponToPost->getId()), Springbot_Services::LISTENER, 'listener');
			$lastFoundCouponId = $couponToPost->getId();
		}
		if (($lastFoundCouponId) && ($lastPostedCouponId != $lastFoundCouponId)) {
			Mage::getModel('core/config')->saveConfig('springbot/tmp/last_coupon_id', $lastFoundCouponId, 'default', 0);
			Mage::getConfig()->cleanCache();
		}
	}

	private function _makeTaskInstance($cmd)
	{
		$taskname = $cmd['command'];
		return Springbot_Services_Tasks::makeTask($taskname, $this->_getParams($cmd));
	}

	private function _getParams($cmd)
	{
		if(is_array($cmd['data'])) {
			return array_merge($cmd['data'], $this->getData());
		} else {
			return $this->getData();
		}
	}


}
