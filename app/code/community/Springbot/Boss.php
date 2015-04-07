<?php

/**
 * Class: Springbot_Boss
 *
 * @author Springbot Magento Integration Team <magento@springbot.com>
 * @version 1.4.0.6
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Springbot_Boss
{
	const SOURCE_BULK_HARVEST  = 'BH';
	const SOURCE_OBSERVER      = 'OB';
	const DATE_FORMAT          = 'Y-m-d H:i:s';
	const NO_SKU_PREFIX        = '_sbentity-';
	const TOKEN_DELIMITER      = '%7';
	const COOKIE_NAME          = 'springbot_redirect_queue';
	const SB_TRACKABLES_COOKIE = '_sbtk';
	const MAXIMUM_IDS_SAVED    = 32;

	/**
	 * Schedule cron job
	 *
	 * @param string $method
	 * @param array $args
	 * @param int $priority
	 * @param string $queue
	 * @param int $storeId
	 * @param int $minutesInFuture
	 */
	public static function scheduleJob($method, array $args, $priority, $queue = 'default', $storeId = null, $minutesInFuture = 0)
	{
		if(self::active() && !empty($method)) {

			if (is_int($minutesInFuture) && ($minutesInFuture > 0)) {
				$nextRunAt = date("Y-m-d H:i:s", strtotime("+{$minutesInFuture} minutes"));
			}
			else {
				$nextRunAt = date("Y-m-d H:i:s");
			}

			$cronModel = Mage::getModel('combine/cron_queue');
			$cronModel->setData(array(
				'method' => $method,
				'args' => json_encode($args),
				'priority' => $priority,
				'command_hash' => sha1($method . json_encode($args)),
				'queue' => $queue,
				'store_id' => $storeId,
				'next_run_at' => $nextRunAt
			));

			$cronModel->insertIgnore();
			self::startWorkManager();
		}
	}

	public static function insertEvent($data)
	{
		if(self::active()) {
			if(!isset($data['type']) || !isset($data['store_id'])) {
				Springbot_Log::error(new Exception("Invalid action attempted to log"));
				return;
			}
			$event = Mage::getModel('combine/action');
			$event->setData($data);
			$event->setVisitorIp(Mage::helper('core/http')->getRemoteAddr(true));
			$event->save();

			Springbot_Log::debug($event->getData());
		}
	}

	public static function addTrackable($type, $value, $quoteId, $customerId, $customerEmail = '', $orderId = null)
	{
		if(self::active()) {
			$trackableModel = Mage::getModel('combine/trackable');
			$trackableModel->setData(array(
				'type' => $type,
				'value' => $value,
				'quote_id' => $quoteId,
				'customer_id' => $customerId,
				'email' => $customerEmail,
				'order_id' => $orderId
			));
			$trackableModel->createOrUpdate();

			// Ensure that trackables in cookie are processed
			foreach($trackableModel->getTrackables() as $type => $value) {
				Springbot_Log::debug("Ensure trackable $type => $value");
				$trackableModel->setData(array(
					'type' => $type,
					'value' => $value,
					'quote_id' => $quoteId,
					'customer_id' => $customerId,
					'email' => $customerEmail,
					'order_id' => $orderId
				));
				$trackableModel->createOrUpdate();
			}
		}
	}

	public static function startWorkManager()
	{
		if(self::active() && !self::isCron() && !self::isPrattler()) {
			$status = Mage::getModel('combine/cron_manager_status');
			if(
				!$status->isBlocked() &&
				!$status->isActive()
			) {
				Springbot_Cli::internalCallback('work:manager');
			}
		}
	}

	/**
	 * Schedule all jobs intended to run in the future
	 *
	 * @param integer $storeId
	 */
	public static function scheduleFutureJobs($storeId = null)
	{
		if (is_null($storeId)) {
			$storeId = Mage::app()->getStore()->getStoreId();
		}

		// Healthcheck uses default query interval
		Springbot_Boss::scheduleJob('cmd:healthcheck', array('s' => $storeId), 5, 'listener', $storeId, 5);

		// Send event log every minute
		Springbot_Boss::scheduleJob('tasks:deliverEventLog', array('s' => $storeId), 5, 'listener', $storeId, 1);

		// Run this in real time, but only every 30 min
		Springbot_Boss::scheduleJob('work:cleanup', array('s' => $storeId), 5, 'listener', $storeId, 30);
	}

	/**
	 * Removes all harvest jobs from the queue
	 *
	 * @param integer $storeId
	 */
	public static function halt($storeId = null)
	{
		$queueDb = new Springbot_Combine_Model_Mysql4_Cron_Queue;
		$queueDb->removeHarvestRows($storeId);
	}

	public static function isCron()
	{
		if (Mage::getStoreConfig('springbot/advanced/harvester_type') == "cron") {
			return true;
		} else {
			return false;
		}
	}


	public static function isPrattler()
	{
		if (Mage::getStoreConfig('springbot/advanced/harvester_type') == "prattler") {
			return true;
		} else {
			return false;
		}
	}

	public static function harvesterType()
	{
		return Mage::getStoreConfig('springbot/advanced/harvester_type');
	}

	public static function storeIdsExist()
	{
		foreach (Mage::app()->getStores() as $store) {
			if (!Mage::getStoreConfig('springbot/config/store_id_' . $store->getId())) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Check if plugin is active and should function properly
	 */
	public static function active()
	{
		$token = Mage::getStoreConfig('springbot/config/security_token');
		return !empty($token);
	}

	public static function setCookie($name, $value)
	{
		Springbot_Log::debug("Saving cookie $name : $value");

		Mage::getModel('core/cookie')->set(
			$name,
			$value,
			strtotime('+365 days'),
			'/', // path
			null, // domain
			null, // secure
			false // httpOnly
		);
	}
}
