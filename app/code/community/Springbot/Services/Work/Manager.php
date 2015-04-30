<?php

class Springbot_Services_Work_Manager extends Springbot_Services
{
	const WORKMANAGER_FILENAME = 'springbot-workmanager';
	const WORKER_PREFIX = 'springbotworker-';
	const SPAWN_WORKER_SECONDS = 2;
	const WORKMANAGER_MAX_TIME = 50;
	const WORKER_TIMEOUT = 600;	// 10 minutes

	protected $_foremanQueued = false;

	public function run()
	{
		Springbot_Log::debug("Starting work manager");
		if (!$this->_managerRunning()) {

			$filename = Mage::getBaseDir('tmp') . DS . self::WORKMANAGER_FILENAME;
			file_put_contents($filename, getmypid() . '-' . time());

			if (file_exists($filename)) {
				if (!$maxWorkers = Mage::getStoreConfig('springbot/advanced/worker_count')) {
					$maxWorkers = 1;
				}

				$start = time();
				do {
					$currentWorkerCount = $this->_getWorkerCount();
					if(!$this->_foremanRunning()) {
						$this->_foremanQueued = true;
						Springbot_Cli::internalCallback('work:runner', array('o' => true));
					}
					else if($currentWorkerCount < $maxWorkers) {
						Springbot_Cli::internalCallback('work:runner');
					}
					sleep(self::SPAWN_WORKER_SECONDS);
					$this->_verifyWorkersRunning();
					$currentWorkerCount = $this->_getWorkerCount();
					$elapsedTime = time() - $start;
				} while (($elapsedTime < self::WORKMANAGER_MAX_TIME) && ($currentWorkerCount > 0) && $this->_hasJobs());
				unlink($filename);
			}

			if($this->_hasJobs()) {
				Springbot_Log::debug("Jobs still queued, restarting manager");
				Springbot_Cli::startWorkManager();
			} else {
				Springbot_Log::debug("No more jobs found. Exiting. Manager will restart on next checkin.");
			}

		}
	}

	public function cleanup()
	{
		$this->_managerRunning();
		$this->_verifyWorkersRunning();
	}

	public function hasWorkers()
	{
		return $this->_getWorkerCount() > 0 && !$this->_managerRunning();
	}

	private function _managerRunning()
	{
		$filename = Mage::getBaseDir('tmp') . DS . self::WORKMANAGER_FILENAME;
		if (file_exists($filename)) {
			list($pid, $startTime) = explode('-', file_get_contents($filename));
			if (is_readable('/proc')) {
				$exists = file_exists("/proc/{$pid}");
			}
			else {
				$exists = ((time() - $startTime) < self::WORKER_TIMEOUT);
			}
			if (!$exists) unlink($filename);
			return $exists;
		}
		return false;
	}

	private function _foremanRunning()
	{
		if($this->_foremanQueued) { return true; }

		$files = @glob(Mage::getBaseDir('tmp') . DS . 'springbotworkerforeman*');
		if ($files === false) return false;
		else return count($files) > 0;
	}

	private function _verifyWorkersRunning()
	{
		foreach ($this->_getWorkerFiles() as $workerFile) {
			if (is_readable('/proc')) {
				list($frontName, $pid) = explode('-', basename($workerFile));
				if (!file_exists("/proc/{$pid}")) {
					Springbot_Log::debug("Procfile not found, removing work file for pid => $pid");
					unlink($workerFile);
				}
			}
			else if ((time() - file_get_contents($workerFile)) > self::WORKER_TIMEOUT) {
				Springbot_Log::debug("/proc not readable and process timed out for pid => $pid");
				unlink($workerFile);
			}
		}
	}

	private function _getWorkerCount()
	{
		return count($this->_getWorkerFiles());
	}

	private function _getWorkerFiles()
	{
		$glob = glob(Mage::getBaseDir('tmp') . DS . 'springbotworker*');
		return $glob ? $glob : array();
	}

	private function _hasJobs()
	{
		return Mage::getModel('combine/cron_queue')->getCollection()->hasJobs();
	}
}
