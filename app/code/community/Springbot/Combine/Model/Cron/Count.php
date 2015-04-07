<?php

class Springbot_Combine_Model_Cron_Count extends Springbot_Combine_Model_Cron
{
	public function _construct()
	{
		$this->_init('combine/cron_count');
	}

	public function increaseCount($storeId, $harvestId, $entityType, $increment)
	{
		$collection = Mage::getModel('combine/cron_count')->getCollection();
		$countResource = Mage::getResourceModel('combine/cron_count');

		// Query to see if count already exists
		$rowCount = $collection->addFieldToFilter('store_id', $storeId)
			->addFieldToFilter('harvest_id', $harvestId)
			->addFieldToFilter('entity', $entityType)
			->getSize();

		// If it doesn't exist yet, create a new row
		if($rowCount == 0) {
			$countResource->createCountRow($storeId, $harvestId, $entityType, $increment);
		}
		else {
			$countResource->increaseCountRow($storeId, $harvestId, $entityType, $increment);
		}
	}

	public function getProcessedCount($storeId, $harvestId, $entityType)
	{
		$countRow = $this->_getEntityCountItem($storeId, $harvestId, $entityType);
		if ($countRow) {
			return $countRow->getCount();
		}
		else {
			return 0;
		}
	}

	public function getEntityStartTime($storeId, $harvestId, $entityType)
	{
		$countRow = $this->_getEntityCountItem($storeId, $harvestId, $entityType);
		if ($countRow) {
			return $countRow->getCreatedAt();
		}
		else {
			return 0;
		}
	}

	public function getEntityCompletedTime($storeId, $harvestId, $entityType)
	{
		$countRow = $this->_getEntityCountItem($storeId, $harvestId, $entityType);
		if ($countRow) {
			return $countRow->getCompleted();
		}
		else {
			return 0;
		}
	}


	private function _getEntityCountItem($storeId, $harvestId, $entityType)
	{
		$collection = Mage::getModel('combine/cron_count')->getCollection();
		$countRow = $collection
			->addFieldToFilter('store_id', $storeId)
			->addFieldToFilter('harvest_id', $harvestId)
			->addFieldToFilter('entity', $entityType)
			->getFirstItem();
		if ($countRow->hasCount()) {
			return $countRow;
		}
		else {
			return null;
		}
	}

	public function setCompletedTime($storeId, $harvestId, $entityType)
	{
		$countResource = Mage::getResourceModel('combine/cron_count');
		$countResource->setCompletedTime($storeId, $harvestId, $entityType);
	}

}
