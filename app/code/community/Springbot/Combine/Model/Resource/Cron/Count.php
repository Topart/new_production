<?php

class Springbot_Combine_Model_Resource_Cron_Count extends Springbot_Combine_Model_Resource_Abstract
{
	public function _construct()
	{
		$this->_init('combine/cron_count', 'id');
	}

	public function createCountRow($storeId, $harvestId, $entityType, $count)
	{
		$countItem = Mage::getModel('combine/cron_count');
		$countItem->setData(array(
			'store_id' => $storeId,
			'harvest_id' => $harvestId,
			'entity' => $entityType,
			'count' => $count
		));
		$this->insertIgnore($countItem);
	}

	public function increaseCountRow($storeId, $harvestId, $entityType, $count)
	{
		$adapter = $this->_getWriter();
		$table = $this->getMainTable();
		$sql = "UPDATE `{$table}` SET `count` = `count` + :count WHERE `store_id` = :store_id AND `harvest_id` = :harvest_id AND `entity` = :entity_type";
		$binds = array(
			'count'			=> $count,
			'store_id'      => $storeId,
			'harvest_id'     => $harvestId,
			'entity_type'   => $entityType,
		);

		$stmt = $adapter->query($sql, $binds);
		return $stmt->rowCount();
	}

	public function setCompletedTime($storeId, $harvestId, $entityType)
	{
		$adapter = $this->_getWriter();
		$table = $this->getMainTable();
		$sql = "UPDATE `{$table}` SET `completed` = NOW() WHERE `store_id` = :store_id AND `harvest_id` = :harvest_id AND `entity` = :entity_type";
		$binds = array(
			'store_id'      => $storeId,
			'harvest_id'     => $harvestId,
			'entity_type'   => $entityType,
		);
		$stmt = $adapter->query($sql, $binds);
	}

	public function clearStoreCounts($storeId)
	{
		$adapter = $this->_getWriter();
		$table = $this->getMainTable();
		$sql = "DELETE FROM `{$table}` WHERE `store_id` = :store_id";
		$binds = array(
			'store_id'      => $storeId,
		);

		$stmt = $adapter->query($sql, $binds);
		return $stmt->rowCount();
	}

	protected function _getWriter()
	{
		return Mage::getSingleton('core/resource')->getConnection('core_write');
	}
}
