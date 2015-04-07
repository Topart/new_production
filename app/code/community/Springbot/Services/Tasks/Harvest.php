<?php

class Springbot_Services_Tasks_Harvest extends Springbot_Services
{
	public function run()
	{
		$harvest = new Springbot_Services_Cmd_Harvest();
		$harvest->run();
		return true;
	}
}




