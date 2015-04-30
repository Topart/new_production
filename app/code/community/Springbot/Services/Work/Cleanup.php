<?php

class Springbot_Services_Work_Cleanup extends Springbot_Services
{
	const UNLOCK_AFTER_X_HOURS = 24;

	public function run()
	{
		$this->_unlockOrphanedRows();
		$this->_unlockForgottenJobs(self::UNLOCK_AFTER_X_HOURS);
	}

	protected function _unlockOrphanedRows()
	{
		Springbot_Log::debug("Unlocking orphaned rows");
		$queueDb = Mage::getResourceModel('combine/cron_queue');
		$status = Mage::getModel('combine/cron_manager_status');
		$pids = $status->getActiveWorkerPids();
		$queueDb->unlockOrphanedRows($pids);
	}

	protected function _unlockForgottenJobs($hoursOld)
	{
		Springbot_Log::debug("Unlocking forgotten jobs");
		$queueDb = Mage::getResourceModel('combine/cron_queue');
		$queueDb->unlockOldRows($hoursOld);
	}
}
