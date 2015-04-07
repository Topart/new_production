<?php

abstract class Springbot_Combine_Model_Resource_Abstract extends Mage_Core_Model_Mysql4_Abstract
{
	public function insertIgnore(Mage_Core_Model_Abstract $object)
	{
		try {
			$table = $this->getMainTable();
			$bind = $this->_prepareDataForSave($object);
			$bind = $this->_convertDatetimesToString($bind);
			$this->_insertIgnore($table, $bind);
		} catch (Exception $e) {
			Springbot_Log::error($e);
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

	// Fixes issue with Magento converting DateTimes to an object and inserting extra quotes
	protected function _convertDatetimesToString($bind) {
		foreach ($bind as $key => $value) {
			if (is_object($value)) {
				$bind[$key] = trim((string)$value, "'");
			}
		}
		return $bind;
	}

	protected function _getHelper()
	{
		return Mage::helper('combine/redirect');
	}
}
