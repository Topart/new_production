<?php

abstract class Springbot_Services_Post extends Springbot_Services_Abstract
{

	public function getDataSource()
	{
		return Springbot_Boss::SOURCE_OBSERVER;
	}
}
