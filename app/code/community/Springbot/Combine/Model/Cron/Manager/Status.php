<?php

class Springbot_Combine_Model_Cron_Manager_Status extends Varien_Object
{
	const ACTIVE = 'active';
	const INACTIVE = 'inactive';
	const BLOCKER = 'springbot-work-mgr.block';

	public function isActive()
	{
		if (is_readable('/proc')) {
			$pid = $this->getPid();
			return !empty($pid) && file_exists("/proc/$pid");
		}
		else {
			$filename = Mage::getBaseDir('tmp') . DS . Springbot_Services_Work_Manager::WORKMANAGER_FILENAME;
			if(file_exists($filename)) {
				list($pid, $startTime) = explode('-', file_get_contents($filename));
				return ((time() - $startTime) < Springbot_Services_Work_Manager::WORKER_TIMEOUT);
			} else {
				return false;
			}
		}
	}

	public function toggle()
	{
		if($this->isActive()) {
			Springbot_Log::debug('Work manager active, halting');
			$this->issueWorkBlocker();
			$this->haltManager($this->getPid());
		} else {
			Springbot_Log::debug('Work manager inactive, starting');
			$this->removeWorkBlocker();
			Springbot_Boss::startWorkManager();
		}
	}

	public function isBlocked()
	{
		return file_exists($this->_getBlockFile());
	}


	public function issueWorkBlocker()
	{
		file_put_contents($this->_getBlockFile(), '');
	}

	public function removeWorkBlocker()
	{
		if($this->isBlocked()) {
			unlink($this->_getBlockFile());
		}
	}

	public function getStatus()
	{
		return $this->isActive() ? self::ACTIVE : self::INACTIVE;
	}

	public function getRuntime()
	{
		$filename = $this->_getWorkmanagerFilename();
		if(file_exists($filename)) {
			return time() - filectime($filename);
		}
	}

	public function getActiveWorkerPids()
	{
		$ids = array();
		foreach($this->_getWorkerFiles() as $file) {
			$matches = array();
			preg_match('/\d+$/', $file, $matches);
			if(isset($matches[0])) {
				$ids[] = $matches[0];
			}
		}
		return $ids;
	}

	public function getPid()
	{
		$filename = $this->_getWorkmanagerFilename();
		if(file_exists($filename)) {
			return file_get_contents($filename);
		} else {
			return null;
		}
	}

	protected function _getBlockFile()
	{
		return Mage::getBaseDir('tmp') . DS . self::BLOCKER;
	}

	protected function _getWorkmanagerFilename()
	{
		return Mage::getBaseDir('tmp') . DS . Springbot_Services_Work_Manager::WORKMANAGER_FILENAME;
	}

	private function _getWorkerFiles()
	{
		return glob(Mage::getBaseDir('tmp') . DS . 'springbotworker*');
	}
}
