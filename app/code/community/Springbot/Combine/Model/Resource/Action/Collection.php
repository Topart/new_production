<?php

class Springbot_Combine_Model_Resource_Action_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
	public function _construct()
	{
		$this->_init('combine/action');
	}

	public function getEvents($storeId, $limit = 200)
	{
		$this->addFieldToFilter('store_id', $storeId)
			->setPage(1, $limit);
		return $this;
	}

	public function getLockedEvents($storeId, $pid = null)
	{
		if($pid) {
			$this->addFieldToFilter('locked_by', $pid);
		}
		$this->addFieldToFilter('store_id', $storeId);
		return $this;
	}
}
