<?php

class Springbot_Services_Work_Stop extends Springbot_Services
{
	public function run()
	{
		if($this->getForce() === true) {
			$this->_getStatus()->issueWorkBlocker();
		}

		do{
			if($pid = $this->getPid()) {
				Springbot_Log::debug("Issuing kill command for manager pid: {$pid}");
				Springbot_Cli::spawn('kill ' . $pid);
			} else {
				Springbot_Log::debug("No active manager found, skipping");
			}

			if($pids = $this->_getStatus()->getActiveWorkerPids()) {
				foreach ($pids as $pid) {
					Springbot_Log::debug("Issuing kill command for worker pid: {$pid}");
					Springbot_Cli::spawn('kill ' . $pid);
				}
			} else {
				Springbot_Log::debug("No active workers found, skipping");
			}

			$this->_getManager()->cleanup();

			sleep(2); // ensure that we don't get jobs spinning up
		} while ($this->_getManager()->hasWorkers());
	}

	public function getPid()
	{
		if(isset($this->_data['pid'])) {
			return parent;
		} else {
			return $this->_getStatus()->getPid();
		}
	}

	protected function _getManager()
	{
		return new Springbot_Services_Work_Manager();
	}
}
