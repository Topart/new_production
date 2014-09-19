<?php

class Springbot_Services_Post_Subscriber extends Springbot_Services_Post
{
	public function run()
	{
		$harvester = Mage::getModel('combine/harvest_subscribers')->setDelete($this->getDelete());
		$harvester->push(Mage::getModel('newsletter/subscriber')->load($this->getStartId()));
		$harvester->postSegment();
	}
}
