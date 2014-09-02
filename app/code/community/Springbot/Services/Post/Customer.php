<?php

class Springbot_Services_Post_Customer extends Springbot_Services_Post
{
	public function run()
	{
		$harvester = Mage::getModel('combine/harvest_customers')->setDelete($this->getDelete());
		$harvester->push(Mage::getModel('customer/customer')->load($this->getStartId()));
		$harvester->postSegment();
	}
}
