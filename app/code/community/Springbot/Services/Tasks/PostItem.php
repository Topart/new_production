<?php

class Springbot_Services_Tasks_PostItem extends Springbot_Services
{
	public function run()
	{
		if($type = ucfirst($this->getCategory())) {
			$classname = 'Springbot_Services_Post_' . $type;
			$instance = new $classname();
			$instance->setEntityId($this->getEntityId());
			$instance->run();
			return true;
		} else {
			throw new Exception("Type not supplied for " . __CLASS__);
			return false;
		}
	}
}
