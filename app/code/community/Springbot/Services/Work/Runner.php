<?php

class Springbot_Services_Work_Runner extends Springbot_Services
{
	public function run()
	{
		if ($this->getIsForeman()) {
			$filename = Mage::getBaseDir('tmp') . DS . 'springbotworkerforeman-' . getmypid();
		}
		else {
			$filename = Mage::getBaseDir('tmp') . DS . 'springbotworker-' . getmypid();
		}

		Springbot_Log::debug("Creating workerfile : $filename");

		file_put_contents($filename, time());

		if(file_exists($filename)) {
			if($this->hasEntityId()) {
				Mage::getModel('combine/cron_worker')->run($this->getIsForeman(), $this->getEntityId());
			}
			else {
				Mage::getModel('combine/cron_worker')->run($this->getIsForeman());
			}

			Springbot_Log::debug('Worker ' . getmypid() . ' spinning down');

			@unlink($filename);
		}
	}

}
