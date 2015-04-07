<?php

class Springbot_Combine_Model_Cron_Worker extends Mage_Core_Model_Abstract
{
	public function run($isForeman = true, $jobId = null)
	{
		Springbot_Log::debug("Starting worker for pid => " . getmypid());
		try{
			if($jobId) {
				// Run only a specific job
				$job = $this->getJob($jobId);
				if($job->hasId()) {
					$job->run();
				}
			}
			else {
				$count = 0;
				$maxJobs = $this->getMaxJobs();
				do {
					if($job = $this->getNextJob($isForeman)) {
						Springbot_Log::debug("Running job #$count for pid => " . getmypid());
						$job->run();
						$count++;
					} else {
						Springbot_Log::debug("No more jobs found");
					}
				} while ($job && ($count < $maxJobs));
			}
		} catch (Exception $e) {
			Springbot_Log::error($e);
		}
	}

	public function cronRun()
	{
		if(Springbot_Boss::isCron()) {
			$status = Mage::getModel('combine/cron_manager_status');
			if(!($status->isBlocked() && $status->isActive())) {
				$this->run(true);
			}
		}
	}

	public function getMaxJobs()
	{
		if (!$maxJobs = Mage::getStoreConfig('springbot/cron/max_jobs')) {
			$maxJobs = 10;
		}
		return $maxJobs;
	}

	public function getBulkJobsToRun($queue)
	{
		return $this->_getCollection()->getPriorityJobs($this->getMaxJobs(), $queue);
	}

	public function getActiveCount()
	{
		return $this->_getCollection()->getActiveCount();
	}

	protected function _cleanup()
	{
		foreach($this->_getFailedJobs() as $job) {
			Springbot_Log::debug("Removing failed job {$job->getMethod()}");
			$job->delete();
		}
	}

	protected function _getFailedJobs()
	{
		return $this->_getCollection()
			->addFieldToFilter('error', array('notnull' => true));
	}

	protected function _getCollection()
	{
		return Mage::getModel('combine/cron_queue')->getCollection();
	}

	public function getNextJob($isForeman)
	{
		return $this->_getCollection()->getNextJob($isForeman);
	}

	public function getJob($jobId)
	{
		return Mage::getModel('combine/cron_queue')->load($jobId);
	}

}
