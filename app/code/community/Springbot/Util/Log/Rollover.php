<?php

class Springbot_Util_Log_Rollover
{
	protected $_globbed = array();

	public function reset()
	{
		$this->_globbed = array();
	}

	public function ensureLogSize()
	{
		Springbot_Log::debug('Ensure log size');

		foreach($this->getLogFilenames() as $filename) {
			if($this->sizeLimitReached($filename)) {
				Springbot_Log::debug('File limit reached for ' . $filename);
				$this->rolloverLog($filename);
			}
		}
	}

	public function rolloverLog($initFilename)
	{
		$filename = $this->_qualify($initFilename);
		Springbot_Log::debug("Rolling log for $filename");
		if(file_exists($filename)) {
			Springbot_Log::debug('Rolling over ' . $filename);
			$id = $this->_getNextLogId($filename);
			rename($filename, $filename . '.' . $id);
			Springbot_Log::release($initFilename);
		}
	}

	public function expireLogs()
	{
		Springbot_Log::debug('Check expire time');

		foreach($this->getLogFilenames() as $filename) {
			foreach($this->getGlobbedFilenames($filename) as $file) {
				$this->_expireFile($file);
			}
		}
	}

	public function getGlobbedFilenames($filename)
	{
		if(!isset($this->_globbed[$filename])) {
			$filename = $this->_qualify($filename);
			$pattern = $filename . '.*';
			$glob = glob($pattern);
			$this->_globbed[$filename] = isset($glob) ? $glob : array();
			Springbot_Log::debug("Checking glob for $pattern, returns " . count($this->_globbed[$filename]) . " results");
		}
		return $this->_globbed[$filename];
	}

	public function getLogFilenames()
	{
		return array(
			Springbot_Log::LOGFILE,
			Springbot_Log::ERRFILE,
			Springbot_Log::HTTPFILE
		);
	}

	public function sizeLimitReached($filename)
	{
		$filename = $this->_qualify($filename);
		$fileLimit = Mage::getStoreConfig('springbot/debug/filesize_limit');

		if(file_exists($filename)) {
			return filesize($filename) > $fileLimit;
		} else {
			return false;
		}
	}

	protected function _getNextLogId($filename)
	{
		$ids = array(0);
		foreach($this->getGlobbedFilenames($filename) as $file) {
			$matches = array();
			preg_match('/\d+$/', $file, $matches);
			if(isset($matches[0])) {
				$ids[] = $matches[0];
			}
		}
		$id = max($ids) + 1;
		Springbot_Log::debug("Next id: $id");
		return $id;
	}

	protected function _qualify($filename)
	{
		return $this->_getLogDir() . DS . basename($filename);
	}

	protected function _getLogDir()
	{
		return Mage::getBaseDir('var') . DS . 'log';
	}

	protected function _expireFile($file)
	{
		if(file_exists($file)) {
			$elapsedSinceModified = time() - filectime($file);
			if($elapsedSinceModified > $this->_getExpireLimit()) {
				Springbot_Log::debug("Removing $file due to expiration");
				unlink($file);
			}
		}
	}

	private function _getExpireLimit()
	{
		// converting days into seconds for comparision to filectime
		return Mage::getStoreConfig('springbot/debug/expire_time_days') * 24 * 60 * 60;
	}
}
