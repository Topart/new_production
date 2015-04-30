<?php

class Springbot_Combine_Model_Resource_Cron_Queue extends Springbot_Combine_Model_Resource_Abstract
{
	public function _construct()
	{
		$this->_init('combine/cron_queue', 'id');
	}

	public function removeHarvestRows($storeId = null, $listenersOnly = true)
	{
		$cronQueueTable = Mage::getSingleton('core/resource')->getTableName('springbot_cron_queue');
		$write = $this->_getWriter();
		if (is_null($storeId)) {
			if ($listenersOnly) {
				$write->query("DELETE FROM `{$cronQueueTable}` WHERE `queue` != 'listener';");
			}
			else {
				$write->query("DELETE FROM `{$cronQueueTable}` WHERE 1;");
			}
		}
		else {
			$sql = $write->quoteInto("DELETE FROM `{$cronQueueTable}` WHERE `store_id` = ?", $storeId);
			$write->query($sql);
		}
	}

	public function removeHarvestRow($rowId)
	{
		$cronQueueTable = Mage::getSingleton('core/resource')->getTableName('springbot_cron_queue');
		$write = $this->_getWriter();
		$sql = $write->quoteInto("DELETE FROM `{$cronQueueTable}` WHERE `id` = ?", $rowId);
		$write->query($sql);
	}

	public function lockRows($rowIds)
	{
		$cronQueueTable = Mage::getSingleton('core/resource')->getTableName('springbot_cron_queue');
		$write = $this->_getWriter();
		$lockedAt = now();
		$lockedBy = getmypid();
		$idsString = implode(', ', $rowIds);
		$write->query("UPDATE `{$cronQueueTable}` SET `locked_at` = '{$lockedAt}', `locked_by` = {$lockedBy} WHERE `id` IN ({$idsString});");
	}

	public function unlockOldRows($hoursOld)
	{
		$cronQueueTable = Mage::getSingleton('core/resource')->getTableName('springbot_cron_queue');
		$write = $this->_getWriter($cronQueueTable);
		$write->query("UPDATE `{$cronQueueTable}` SET `locked_at` = NULL, `locked_by` = NULL WHERE `locked_at` < DATE_SUB(NOW(), INTERVAL {$hoursOld} HOUR)");
	}

	public function unlockOrphanedRows($activeIds = array())
	{
		$cronQueueTable = Mage::getSingleton('core/resource')->getTableName('springbot_cron_queue');
		$write = $this->_getWriter($cronQueueTable);
		$sql = "UPDATE `{$cronQueueTable}` SET `locked_at` = NULL, `locked_by` = NULL WHERE (`locked_by` IS NOT NULL OR `locked_at` IS NOT NULL) ";
		if(count($activeIds)) {
			$sql = $write->quoteInto($sql . "AND `locked_by` NOT IN (?)", $activeIds);
		}
		Springbot_Log::debug($sql);
		$write->query($sql);
	}

	public function resetRetries()
	{
		$cronQueueTable = Mage::getSingleton('core/resource')->getTableName('springbot_cron_queue');
		$write = $this->_getWriter($cronQueueTable);
		$write->query("UPDATE `{$cronQueueTable}` SET `attempts` = 0 WHERE 1");
	}


	protected function _getWriter()
	{
		return Mage::getSingleton('core/resource')->getConnection('core_write');
	}
}
