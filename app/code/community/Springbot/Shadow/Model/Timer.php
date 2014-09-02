<?php

class Springbot_Shadow_Model_Timer
{
	const DEFAULT_POLLING_INTERVAL = 5; // minutes

	protected $_task;
	protected $_storeId;
	protected $_interval;

	public function __construct($task, $storeId, $interval)
	{
		$this->_task = $task;
		$this->_storeId = $storeId;
		$this->_interval = is_null($interval) ? $this->_getQueryInterval() : $interval;
	}

	public static function fire($task, $storeId, $interval = null)
	{
		$ins = new Springbot_Shadow_Model_Timer($task, $storeId, $interval);
		if($ins->doRunTask()) {
			Springbot_Log::debug("Firing $task for store_id: $storeId");
			$ins->_setCache(time());
			return true;
		} else {
			return false;
		}
	}

	public function doRunTask()
	{
		$intervalDiff = (time() - $this->_getCache()) / 60;
		return $intervalDiff > $this->_interval;
	}

	protected function _getQueryInterval()
	{
		$interval = Mage::getStoreConfig('springbot/config/query_interval');
		if(empty($interval) || !isset($interval)) {
			$interval = self::DEFAULT_POLLING_INTERVAL;
		}
		return $interval;
	}

	protected function _getCache()
	{
		$fname = $this->_getFilename();
		$lastFired = 0;

		if(file_exists($fname)) {
			if($fHandle = $this->_openCacheWithLock($fname,'r')) {
				$lastFired = trim(fread($fHandle, 128));
				fclose($fHandle);
			}
		}
		return $lastFired;
	}

	protected function _setCache($value)
	{
		if ($fHandle = $this->_openCacheWithLock($this->_getFilename(),'w')) {
			fwrite($fHandle, $value."\n");
			fclose($fHandle);
		}
		return $this;
	}

	protected function _getFilename()
	{
		return Mage::getBaseDir('tmp') . DS . 'sb-cache-' . $this->_task . '-' . $this->_storeId . '.dat';
	}

	private function _openCacheWithLock($fileName, $mode = 'w')
	{
		return fopen($fileName, $mode);
	}
}
