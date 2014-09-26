<?php

abstract class Springbot_Combine_Model_Resource_Abstract extends Mage_Core_Model_Mysql4_Abstract
{
	public function insertIgnore(Mage_Core_Model_Abstract $object)
	{
		try {
			$table = $this->getMainTable();
			$bind = $this->_prepareDataForSave($object);
			$this->_insertIgnore($table, $bind);
		} catch (Exception $e) {
			Mage::logException($e);
		}
	}

	protected function _insertIgnore($table, array $bind)
	{
		$adapter = $this->_getWriteAdapter();

		// extract and quote col names from the array keys
		$cols = array();
		$vals = array();
		foreach ($bind as $col => $val) {
			$cols[] = $adapter->quoteIdentifier($col, true);
			$vals[] = '?';
		}

		// build the statement
		$sql = "INSERT IGNORE INTO "
			. $adapter->quoteIdentifier($table, true)
			. ' (' . implode(', ', $cols) . ') '
			. 'VALUES (' . implode(', ', $vals) . ')';

		Springbot_Log::debug($sql);
		Springbot_Log::debug('BIND : '.implode(', ', $bind));

		// execute the statement and return the number of affected rows
		$stmt = $adapter->query($sql, array_values($bind));
		return $stmt->rowCount();
	}

	protected function _getHelper()
	{
		return Mage::helper('combine/redirect');
	}
}
