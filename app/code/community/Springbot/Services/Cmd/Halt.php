<?php

class Springbot_Services_Cmd_Halt extends Springbot_Services
{
	public function run()
	{
		if (isset($this->_data['halt_command'])) {
			$out = Springbot_Boss::halt($this->getHaltCommand());
		}
		else {
			$out = Springbot_Boss::halt();
		}
		print $out . PHP_EOL;
	}
}
