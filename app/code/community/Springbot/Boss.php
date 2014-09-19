<?php

class Springbot_Boss
{
	private static $_loggers;
	private static $_currentStore;

	const EVENT_FILENAME      = 'Springbot-EventHistory.csv';
	const SOURCE_BULK_HARVEST = 'BH';
	const SOURCE_OBSERVER     = 'OB';
	const DATE_FORMAT         = 'Y-m-d H:i:s';
	const NO_SKU_PREFIX		  = '_sbentity-';


	/**
	 * Schedule cron job
	 *
	 * @param string $method
	 * @param array $args
	 * @param int $priority
	 * @param string $queue
	 * @param int $storeId
	 * @param bool $requiresAuth
	 */
	public static function scheduleJob($method, $args, $priority, $queue = 'default', $storeId = null, $requiresAuth = true)
	{
		if($requiresAuth && !self::storeIdsExist()) {
			Springbot_Log::debug('Not authenticated, job not queued');
		}
		else {
			$cronner = Mage::getModel('combine/cron_queue');
			$cronner->setData(array(
				'method' => $method,
				'args' => json_encode($args),
				'priority' => $priority,
				'command_hash' => sha1($method . json_encode($args)),
				'queue' => $queue,
				'store_id' => $storeId
			));

			$cronner->insertIgnore();
			self::startWorkManager();
		}
	}

	public static function startWorkManager()
	{
		$status = Mage::getModel('combine/cron_manager_status');
		if(
			!$status->isBlocked() &&
			!$status->isActive()
		) {
			Springbot_Boss::internalCallback('work:manager');
		}
	}

	/**
	 *
	 *
	 * @param string $method
	 * @param array $args
	 * @param bool $background
	 */
	public static function internalCallback($method, $args = array(), $background = true)
	{
		$bkg = $background ? '&' : '';
		$fmt = self::buildFlags($args);
		$php = Mage::helper('combine/harvest')->getPhpExec();
		$dir = Mage::getBaseDir();
		$err = Mage::helper('combine')->getSpringbotErrorLog();
		$log = Mage::helper('combine')->getSpringbotLog();
		$nohup = Mage::helper('combine')->nohup();
		$nice = Mage::helper('combine')->nice();

		$cmd = "{$nohup} {$nice} {$php} {$dir}/shell/springbot.php {$fmt} {$method} >> {$log} 2>> {$err} {$bkg}";
		return self::spawn($cmd);
	}

	/**
	 * Build cli flags from arg array
	 *
	 * @param array $args
	 * @return string
	 */
	public static function buildFlags($args)
	{
		$fmt = array();

		foreach($args as $flag => $arg) {
			if(is_int($flag)) {
				$flag = $arg;
				$arg = '';
			}
			$fmt[] = "-$flag $arg";
		}
		return implode(' ', $fmt);
	}

	/**
	 * Spawn system callback with any available system command
	 *
	 * @param string $command
	 * @param int $return_var
	 */
	public static function spawn($command, &$return_var = 0)
	{
		Springbot_Log::debug($command);
		if(function_exists('system')) {
			$ret = system($command, $return_var);
		} else if(function_exists('exec')) {
			$ret = exec($command, $return_var);
		} else if(function_exists('passthru')) {
			$ret = passthru($command, $return_var);
		} else if(function_exists('shell_exec')) {
			$ret = shell_exec($command);
		} else {
			throw new Exception('Program execution function not found!');
		}
		Springbot_Log::debug($ret);
		return $ret;
	}

	public static function launchHarvest()
	{
		Mage::helper('combine/harvest')->truncateEngineLogs();
		Springbot_Boss::internalCallback('cmd:harvest');
	}

	/**
	 * This method kills all processes which contain 'Harvest' in the command by default.
	 * Use carefully!
	 *
	 * @param string $toHalt
	 */
	public static function halt()
	{
        $queueDb = new Springbot_Combine_Model_Mysql4_Cron_Queue;
        $queueDb->removeHarvestRows();
	}

	public static function haltStore($storeId)
	{
		$queueDb = new Springbot_Combine_Model_Mysql4_Cron_Queue;
		$queueDb->removeStoreHarvestRows($storeId);
	}

	public static function setActive($storeId)
	{
		self::$_currentStore = $storeId;
	}

	public static function getEventHistoryFilename()
	{
		return Mage::getBaseDir('var') . DS . 'log' . DS . self::EVENT_FILENAME;
	}

	public static function isCron()
	{
		return Mage::getStoreConfig('springbot/cron/enabled');
	}

	public static function storeIdsExist()
	{
		$configValues = Mage::getStoreConfig('springbot/config');
		$storeIdStr = 'store_id_';
		foreach ($configValues as $configName => $configValue) {
			if(substr($configName, 0, strlen($storeIdStr)) == $storeIdStr) {
				if (!is_numeric($configValue)) {
					return false;
				}
			}
		}
		return true;
	}

}
