<?php

class Springbot_Combine_Model_Resource_Trackable extends Mage_Core_Model_Mysql4_Abstract
{
	public function _construct()
	{
		$this->_init('combine/trackable', 'id');
	}

	public function create(Springbot_Combine_Model_Trackable $object)
	{
		$adapter = $this->_getWriteAdapter();

		$select = $adapter->select()
			->from($this->getMainTable())
			->where('quote_id = ?', $object->getQuoteId())
			->where('type = ?', $object->getType());

		if(!($row = $adapter->fetchRow($select))) {
			Springbot_Log::debug("Creating trackable {$object->getType()} : {$object->getValue()}");
			$adapter->insert($this->getMainTable(), $object->getData());
			$object->setId($adapter->lastInsertId($this->getMainTable()));
			return $object;
		} else {
			Springbot_Log::debug("Trackable {$object->getType()} : {$object->getValue()} exists, updating");
			$object->setId($row['id']);

			$this->save($object);
			return $object;
		}
	}
}
