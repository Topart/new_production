<?php

class Springbot_Combine_Model_Resource_Cron_Count_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
	public function _construct()
	{
		$this->_init('combine/cron_count');
	}


}
