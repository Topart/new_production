<?php

abstract class Springbot_Services_Post extends Springbot_Services
{
	public function getDataSource()
	{
		return Springbot_Boss::SOURCE_OBSERVER;
	}
}
