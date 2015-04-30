<?php

class Springbot_Combine_Model_Cron_Queue_Batch extends Varien_Object
{
	protected $_stack = array();

	public function schedule($method, $args, $priority, $queue = 'default', $storeId = null, $requiresAuth = true)
	{
		$this->push(array(
			'method' => $method,
			'args' => json_encode($args),
			'priority' => $priority,
			'command_hash' => sha1($method . json_encode($args)),
			'queue' => $queue,
			'store_id' => $storeId
		));
		return $this;
	}

	public function insert()
	{
		if($rows = $this->_rowCount()) {
			$sql = $this->toSql();
			Springbot_Log::info("Inserting {$rows} rows into {$this->_getTablename()}");
			$rows = $this->_getWriter()->query($sql);
		}
	}

	public function push($args)
	{
		if($this->_isValid($args)) {
			$row = $this->_getRowModel();
			$row->setData($args);
			$this->_stack[] = $row->toString();
		}
		return $this;
	}

	public function toSql()
	{
		$columns = $this->_getRowModel()->getColumnNames();
		$table = $this->_getTablename();
		$rows = $this->_rowsToSql();
		return "INSERT IGNORE INTO {$table} ({$columns}) VALUES {$rows}";
	}

	protected function _isValid($args)
	{
		return isset($args['method']);
	}

	protected function _rowsToSql()
	{
		return implode(', ', $this->_stack);
	}

	protected function _rowCount()
	{
		return count($this->_stack);
	}

	protected function _getRowModel()
	{
		return Mage::getModel('combine/cron_queue_batch_row');
	}

	protected function _getTablename()
	{
		return Mage::getSingleton('core/resource')->getTableName('springbot_cron_queue');
	}

	protected function _getWriter()
	{
		return Mage::getSingleton('core/resource')->getConnection('core_write');
	}
}
