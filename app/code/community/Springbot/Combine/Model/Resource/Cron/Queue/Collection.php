<?php

class Springbot_Combine_Model_Resource_Cron_Queue_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
	const ATTEMPT_LIMIT = 10;

	public function _construct()
	{
		$this->_init('combine/cron_queue');
	}

	/**
	 * Get jobs based on priority and created at
	 * FIFO, ordered on priority (0 is top priority)
	 *
	 * @param int|string|null $limit
	 */
	public function getPriorityJobs($limit, $queue = null, $isForeman = true)
	{
		$this->getSelect()
			->where('locked_at IS NULL')
			->where('attempts < ?', $this->getAttemptLimit())
			->where(
				"(next_run_at IS NULL OR next_run_at < ?)",
				date("Y-m-d H:i:s")
			);

		// Only foreman can process the default queue
		if(!$isForeman) {
			$this->getSelect()->where("queue != 'default'");
		}

		$this->getSelect()
			->order(array('priority ASC', 'id ASC'));

		if(!empty($limit)) {
			$this->getSelect()->limit($limit);
		}

		if ($queue) {
			$this->getSelect()->where('queue = "' . $queue .'" ');
		}

		return $this;
	}

	public function getNextJob($isForeman)
	{
		$col = $this->getPriorityJobs(1, null, $isForeman);

		if($col && $col->getSize() > 0) {
			return $col->getFirstItem();
		} else {
			return false;
		}
	}

	public function hasJobs()
	{
		return $this->getPriorityJobs(1)->getSize() > 0;
	}

	public function getAttemptLimit()
	{
		return self::ATTEMPT_LIMIT;
	}

	public function getActiveCount()
	{
		return $this->getPriorityJobs(1)->getSize();
	}
}
