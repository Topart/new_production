<?php

class Springbot_Combine_Model_Cron_Queue extends Springbot_Combine_Model_Abstract
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
			'error'	=> null
		));
		$this->save();
	}

	public function run()
	{
		Springbot_Log::debug("Running ".__CLASS__);
		$return = true;
		$class = $this->getInstance();
		$class->setData($this->getParsedArgs());
		$this->_pre();

		try {
			$class->run();
		} catch (Exception $e) {
			$this->setError($e->getMessage());
			// Lower priority for failed job - keeping order intact
			$this->setPriority($this->getPriority() + Springbot_Services_Priority::FAILED);
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
		return $return;
	}

	protected function _post()
	{
		if(!$this->hasError()) {
			$this->delete();
		} else {
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
