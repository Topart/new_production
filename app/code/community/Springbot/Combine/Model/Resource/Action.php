<?php

class Springbot_Combine_Model_Resource_Action extends Springbot_Combine_Model_Resource_Abstract
{
	public function _construct()
	{
		$this->_init('combine/action', 'id');
	}

	public function lockEvents($pid, $storeId, $count)
	{
		$count = (int) $count;
		$vars = array(
			'pid' => $pid,
			'store_id' => $storeId,
		);
		$cronEventsTable = Mage::getSingleton('core/resource')->getTableName('springbot_actions');
		$write = $this->_getWriter();
		$write->query(
			"UPDATE `{$cronEventsTable}`
				SET `locked_by` = :pid, `locked_at` = NOW()
				WHERE `store_id` = :store_id
				ORDER BY `id`
				LIMIT $count",
			$vars
		);
	}

	public function removeEvents($pid = null)
	{
		$cronEventsTable = Mage::getSingleton('core/resource')->getTableName('springbot_actions');
		$write = $this->_getWriter();
		$sql = $write->quoteInto("DELETE FROM `{$cronEventsTable}` WHERE `locked_by` = ?;", $pid);
		$write->query($sql);
	}

	public function removeStoreEventRows($storeId, $pid = null)
	{
		if (is_numeric($storeId) && is_numeric($limit)) {
			$cronEventsTable = Mage::getSingleton('core/resource')->getTableName('springbot_actions');
			$write = $this->_getWriter();
			$sql = $write->quoteInto("DELETE FROM `{$cronEventsTable}` WHERE `store_id` = ?", $storeId);

			if($pid) {
				$sql .= " AND `locked_by` = $pid";
			}

			$write->query($sql);
		}
	}

	public function releaseLocksForPid($pid)
	{
		$cronEventsTable = Mage::getSingleton('core/resource')->getTableName('springbot_actions');
		$write = $this->_getWriter();
		$sql = $write->quoteInto("UPDATE `{$cronEventsTable}` SET `locked_by` = NULL, `locked_at` = NULL WHERE `locked_by`", $pid);
		$write->query($sql);
	}

	public function releaseOldLocks($hoursOld)
	{
		$cronEventsTable = Mage::getSingleton('core/resource')->getTableName('springbot_actions');
		$write = $this->_getWriter();
		$sql = $write->quoteInto("UPDATE `{$cronEventsTable}` SET `locked_by` = NULL, `locked_at` = NULL WHERE `locked_at` < DATE_SUB(NOW(), INTERVAL ? HOUR)", $hoursOld);
		$write->query($sql);
	}

	public function unlockActions()
	{
		$cronEventsTable = Mage::getSingleton('core/resource')->getTableName('springbot_actions');
		$write = $this->_getWriter();
		$write->query("UPDATE `{$cronEventsTable}` SET `locked_by` = NULL, `locked_at` = NULL WHERE 1");
	}

	protected function _getWriter()
	{
		return Mage::getSingleton('core/resource')->getConnection('core_write');
	}
}
