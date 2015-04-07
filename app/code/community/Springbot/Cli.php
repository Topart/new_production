<?php

/**
 * Class: Springbot_Cli
 *
 * @author Springbot Magento Integration Team <magento@springbot.com>
 * @version 1.4.0.0
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Springbot_Cli
{

	private static $_phpExec;

	/**
	 * Intended to be a wrapper for interalCallback to route cronned
	 * instances to the cron queue.  We do not want to send jobs in
	 * the work service here, as they wind up in a loop.
	 *
	 * @param string $method
	 * @param array $args
	 */
	public static function async($method, $args = array())
	{
		if (Springbot_Boss::isCron() || Springbot_Boss::isPrattler()) {
			Springbot_Boss::scheduleJob($method, $args, 1);
		} else {
			self::internalCallback($method, $args, true);
		}
	}

	/**
	 *
	 * @param string $method
	 * @param array $args
	 * @param bool $background
	 */
	public static function internalCallback($method, $args = array(), $background = true)
	{
		$bkg = $background ? '&' : '';
		$fmt = self::buildFlags($args);
		$php = self::getPhpExec();
		$dir = Mage::getBaseDir();
		$err = Springbot_Log::getSpringbotErrorLog();
		$log = Springbot_Log::getSpringbotLog();
		$nohup = self::nohup();
		$nice = self::nice();

		$cmd = "{$nohup} {$nice} {$php} {$dir}/shell/springbot.php {$fmt} {$method} >> {$log} 2>> {$err} {$bkg}";
		return self::spawn($cmd);
	}

	public static function nohup()
	{
		return Mage::getStoreConfig('springbot/advanced/nohup') ? 'nohup' : '';
	}

	public static function nice()
	{
		return Mage::getStoreConfig('springbot/advanced/nice') ? 'nice' : '';
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
		self::async('cmd:harvest');
	}

	public static function launchHarvestInline() {
		$harvest = new Springbot_Services_Cmd_Harvest();
		$harvest->run();
	}


	public static function startWorkManager()
	{
		$status = Mage::getModel('combine/cron_manager_status');
		if (!$status->isBlocked() && !$status->isActive()) {
			self::internalCallback('work:manager');
		}
	}

	public static function haltManager($pid)
	{
		self::internalCallback('work:stop', array('p' => $pid));
	}

	public static function postItem($type, $id)
	{
		self::async(
			"post:$id",
			array('i' => $id)
		);
	}

	public static function resumeHarvest()
	{
		self::async('work:manager');
	}

	/**
	 * Get PHP executable path
	 *
	 * @return string
	 */
	public static function getPhpExec()
	{
		if(!isset(self::$_phpExec)) {
			try {
				$php = Mage::getStoreConfig('springbot/config/php_exec');

				if((empty($php))) {
					// This prevents the system command from outputting to apache
					ob_start();
					if(empty($php) || !file_exists($php)) {
						$php = self::spawn('which php5 2> /dev/null');
					}
					if(empty($php) || !file_exists($php)) {
						$php = self::spawn('which php 2> /dev/null');
					}
					if(empty($php) || !file_exists($php)) {
						$php = 'php';
					}
					ob_end_clean();
				}
				self::$_phpExec = $php;
			}
			catch (Exception $e) {
				return '';
			}
		}
		return self::$_phpExec;
	}
}
