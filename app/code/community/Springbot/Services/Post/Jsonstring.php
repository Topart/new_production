<?php

class Springbot_Services_Post_Jsonstring extends Springbot_Services_Post
{
	public function run()
	{
		Mage::helper('combine')->apiPostWrapped(parent::getData('method'), parent::getData('json'));
	}
}



