<?php

class Springbot_Combine_Model_Cron_Queue extends Springbot_Combine_Model_Cron
{
	const FAILED_JOB_PRIORITY = 9;

	public function _construct()
	{
		$this->_init('combine/cron_queue');
	}

	public function save()
	{
		if($this->_validate()) {
			return parent::save();
		} else {
			Springbot_Log::debug(__CLASS__." invalid, not saving!");
			Springbot_Log::debug($this->getData());
		}
	}

	protected function _validate()
	{
		return $this->hasMethod();
	}

	protected function _pre()
	{
		$this->addData(array(
			'attempts' => $this->getAttempts() + 1,
			'run_at' => now(),
			'locked_at' => now(),
			'locked_by' => getmypid(),
			'next_run_at' => $this->_calculateNextRunAt(),
			'error' => null
		));
		$this->save();
	}

	public function run()
	{
		try {
			$maxJobTime = Mage::getStoreConfig('springbot/advanced/max_job_time');
			if (is_int($maxJobTime)) {
				set_time_limit(Mage::getStoreConfig('springbot/advanced/max_job_time'));
			}

			$return = true;
			$class = $this->getInstance();

			if($class) {
				$class->setData($this->getParsedArgs());
				$this->_pre();
				$class->run();
			} else {
				$this->delete();
			}
		}
		catch (Exception $e) {
			$this->setError($e->getMessage());
			// Lower priority for failed job - keeping order intact
			$this->setPriority($this->getPriority() + Springbot_Services::FAILED);
			$return = false;
			if ($this->getAttempts() >= Springbot_Combine_Model_Resource_Cron_Queue_Collection::ATTEMPT_LIMIT) {
				Springbot_Log::remote(
					"Job failed multiple times. Method: {$this->getMethod()}, Args: {$this->getArgs()}, Error: {$this->getError()}",
					$this->getStoreId(),
					self::FAILED_JOB_PRIORITY
				);
			}
		}
		$this->_post();

		try {
			Springbot_Log::debug("Scheduling future jobs");
			Springbot_Boss::scheduleFutureJobs($class->getStoreId());

			if (is_object($class)) {
				$class->doFinally();
			}
		}
		catch (Exception $e) {
			Springbot_Log::error($e);
		}

		return $return;
	}

	protected function _calculateNextRunAt()
	{
		$attempts = $this->getAttempts();
		$expMinutes = pow(2, $attempts);
		$nextRun = date("Y-m-d H:i:s", strtotime("+$expMinutes minutes"));

		Springbot_Log::debug('Next run at: ' . $nextRun);
		return $nextRun;
	}

	protected function _post()
	{
		if(!$this->hasError()) {
			$this->delete();
		}
		else {
			$this->addData(array(
				'locked_at' => null,
				'locked_by' => null,
			))->save();
		}

	}

	public function getInstance()
	{
		return Springbot_Services_Registry::getInstance($this->getMethod());
	}

	public function getParsedArgs()
	{
		$args = (array) json_decode($this->getArgs());
		return Springbot_Services_Registry::parseOpts($args);
	}
}
