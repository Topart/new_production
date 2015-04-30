<?php

class Springbot_Services_Tasks_ClearCache extends Springbot_Services
{
	public function run()
	{
		try {
			$allTypes = Mage::app()->useCache();
			foreach($allTypes as $type => $blah) {
				Mage::app()->getCacheInstance()->cleanType($type);
			}
			return true;
		}
		catch (Exception $e) {
			return array(
				'success' => false,
				'message' => $e->getMessage()
			);
		}
	}
}




