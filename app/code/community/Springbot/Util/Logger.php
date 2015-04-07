<?php

class Springbot_Util_Logger
{
	const SIMPLE_FORMAT   = '%message%';
	const DEFAULT_FORMAT  = '%timestamp% %priorityName% (%priority%): %message%';
	const EXPANDED_FORMAT = '%timestamp% %className%%callType%%method%(%line%) [%priorityName%] : %message%';

	protected static $_loggers = array();

	protected $_format;

	public function log($message, $level, $file, $format = null, $extras = null)
	{
		switch ($format) {
			case 'simple':
				$this->_format = self::SIMPLE_FORMAT;
				break;
			case 'expanded':
				$this->_format = self::EXPANDED_FORMAT;
				break;
			default:
				$this->_format = self::DEFAULT_FORMAT;
		}
		$this->_log($message, $level, $file, $extras);
	}

	public function release($filename)
	{
		Springbot_Log::debug('Releasing log for ' . $filename);
		unset(self::$_loggers[$filename]);
		$this->_provisionLogFile($filename);
	}

	protected function _log($message, $level, $file, $extras = null)
	{
		try {
			if (!isset(self::$_loggers[$file])) {
				$this->_provisionLogFile($file);
			}

			if (is_array($message) || is_object($message)) {
				$message = print_r($message, true);
			}

			self::$_loggers[$file]->log($message, $level, $extras);
		}
		catch (Exception $e) {
		}
	}

	private function _provisionLogFile($file)
	{
		$logDir = Mage::getBaseDir('var') . DS . 'log';
		$logFile = $logDir . DS . $file;

		if (!is_dir($logDir)) {
			mkdir($logDir);
			chmod($logDir, 0777);
		}

		if (!file_exists($logFile)) {
			file_put_contents($logFile, '');
			chmod($logFile, 0777);
		}

		$format = $this->_format . PHP_EOL;

		$formatter = new Zend_Log_Formatter_Simple($format);
		$writer = new Zend_Log_Writer_Stream($logFile);
		$writer->setFormatter($formatter);
		self::$_loggers[$file] = new Zend_Log($writer);

	}
}
