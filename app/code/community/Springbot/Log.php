<?php

/**
 * Class: Springbot_Log
 *
 * @author Springbot Magento Integration Team <magento@springbot.com>
 * @version 1.4.0.0
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Springbot_Log
{
	const EMERG   = 0;  // Emergency: system is unusable
	const ALERT   = 1;  // Alert: action must be taken immediately
	const CRIT    = 2;  // Critical: critical conditions
	const ERR     = 3;  // Error: error conditions
	const WARN    = 4;  // Warning: warning conditions
	const NOTICE  = 5;  // Notice: normal but significant condition
	const INFO    = 6;  // Informational: informational messages
	const DEBUG   = 7;  // Debug: debug messages

	const LOGFILE = 'Springbot.log';
	const ERRFILE = 'Springbot.err';
	const HTTPFILE = 'Springbot-Http.log';

	protected static $_logger;

	public static function getFormat()
	{
		return Mage::getStoreConfig('springbot/debug/log_format');
	}

	public static function getExtras()
	{
		$caller = Springbot_Util_Caller::find(4);
		if(self::getFormat() == 'expanded') {
			return array(
				'className' => $caller->class,
				'method' => $caller->method,
				'callType' => $caller->call_type,
				'line' => $caller->line,
			);
		}
	}

	public static function logger()
	{
		if(!isset(self::$_logger)) {
			self::$_logger = new Springbot_Util_Logger();
		}
		return self::$_logger;
	}

	public static function release($filename)
	{
		self::logger()->release($filename);
	}

	private static function _log($message, $level, $fmt = null, $file = self::LOGFILE)
	{
		if(self::_levelAllowed($level)) {
			if(!$fmt) {
				$fmt = self::getFormat();
			}
			self::logger()->log($message, $level, $file, $fmt, self::getExtras());
		}
	}

	public static function debug($message)
	{
		self::_log($message, Zend_Log::DEBUG);
	}

	public static function harvest($message, $remote = false, $storeId = 1)
	{
		if(is_null($storeId)) {
			$storeId = isset(self::$_currentStore) ? self::$_currentStore : 1;
		}
		self::_log($message, Zend_Log::CRIT, 'simple', self::LOGFILE);

		if($remote) {
			self::remote($message, $storeId);
		}
	}

	public static function info($message)
	{
		self::_log($message, Zend_Log::INFO);
	}

    public static function getSpringbotErrorLog()
    {
        return Mage::getBaseDir('log') . DS . Springbot_Log::ERRFILE;
    }

    public static function getSpringbotLog()
    {
        return Mage::getBaseDir('log') . DS . Springbot_Log::LOGFILE;
    }


    public static function error(Exception $e)
	{
		if(is_string($e)) {
			$e = new Exception($e);
		}
		self::_log("\n" . $e->__toString(), Zend_Log::ERR, 'default', self::ERRFILE);
	}

	public static function http($message)
	{
		if(Mage::getStoreConfig('springbot/debug/log_http')) {
			if(Mage::getStoreConfig('springbot/debug/pretty_print')) {
				$message = Zend_Json::prettyPrint($message, array("indent" => "  "));
			}
			// lowest possible level - we have another setting controlling this logging
			self::_log($message, Zend_Log::CRIT, 'simple', 'Springbot-Http.log');
		}
	}

	public static function remote($message, $id = 1, $priority = 5, $alert = false)
	{
		$id = (is_null($id)) ? 1 : $id;
		if($storeId = Mage::helper('combine/harvest')->getSpringbotStoreId($id)) {
			$ar = array(
				'store_id' => $storeId,
				'event_time' => Mage::helper('combine')->formatDateTime(),
				'store_url' => Mage::helper('combine/harvest')->getStoreUrl($id),
				'remote_addr' => self::getRemoteAddress(),
				'priority' => $priority,
				'description' => $message,
			);

			if($alert) {
				$ar['log_type'] = 'ALERT';
			}
			$struct = array($storeId => $ar);
			$api = Mage::getModel('combine/api');
			$payload = $api->wrap('logs', $struct);
			$api->reinit()->call('logs', $payload);
		}
	}

	public static function getRemoteAddress()
	{
		return isset($_SERVER['REMOTE_ADDR']) ? $remAddr=$_SERVER['REMOTE_ADDR'] : null;
	}

	private static function _levelAllowed($level)
	{
		return $level <= Mage::getStoreConfig('springbot/debug/log_level');
	}

	public static function printLine($remote = false)
	{
		Springbot_Log::harvest('--------------------------------------------------------------------------------', $remote);
	}



}
