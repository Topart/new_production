<?php

class Springbot_Combine_Model_Cron_Manager_Status extends Varien_Object
{
	const ACTIVE = 'active';
	const INACTIVE = 'inactive';
	const BLOCKER = 'springbot-work-mgr.block';

	public function isActive()
	{
		$pid = $this->getPid();
		return !empty($pid) && file_exists("/proc/$pid");
	}

	public function toggle()
	{
		if($this->isActive()) {
			Springbot_Log::debug('Work manager active, halting');
			$this->issueWorkBlocker();
			$this->haltManager();
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

	public function haltManager()
	{
		Springbot_Boss::internalCallback('work:stop', array('p' => $this->getPid()));
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

	public function getSched()
	{
		if($pid = $this->getPid()) {
			$sched = array();
			$handler = fopen('/proc/' . $pid . '/sched', 'r');
			while($line = fgets($handler)) {
				$pieces = array();
				if(preg_match('/^(\S+)\s+:\s+([0-9.]+)$/', $line, $pieces)) {
					$sched[$pieces[1]] = $pieces[2];
				}
			}
			fclose($handler);
			return $sched;
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
