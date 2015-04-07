<?php

class Springbot_Combine_Model_Cron_Queue_Batch_Row extends Varien_Object
{
	protected $_schema = array(
		'method',
		'args',
		'priority',
		'command_hash',
		'queue',
		'store_id',
	);

	public function getSchema()
	{
		return $this->_schema;
	}

	public function getColumnNames()
	{
		return '`' . implode('`,`', $this->getSchema()) . '`';
	}

	public function toString($format='')
	{
		$res = $this->_getResource();
		$quoted = array();
		foreach ($this->getSchema() as $column) {
			$value = $this->getData($column);
			$quoted[] = !empty($value) ? $res->quote($value) : 'NULL';
		}
		return '(' . implode(',', $quoted) . ')';
	}

	protected function _getResource()
	{
		return Mage::getSingleton('core/resource')->getConnection('core_write');
	}
}
