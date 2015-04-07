<?php

class Springbot_Services_Tasks_KillHarvest extends Springbot_Services
{
	public function run()
	{
		Springbot_Boss::halt();
		return true;
	}
}
