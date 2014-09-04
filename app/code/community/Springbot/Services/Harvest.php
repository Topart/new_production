<?php

abstract class Springbot_Services_Harvest extends Springbot_Services_Abstract
{
	public function run()
	{
		$mb = round(memory_get_peak_usage(true) / pow(1024, 2), 2);

		$msg = "{$this->getHarvesterName()} block {$this->getSegmentMin()} : {$this->getSegmentMax()} posted [{$this->getProcessedCount()} overall]";
		$msg .= " | " . $mb . ' MB';
		$msg .= " | {$this->getRuntime()} sec";

		Springbot_Log::harvest($msg);
		$countObject = Mage::getModel('combine/cron_count');
		$countObject->increaseCount($this->getStoreId(), $this->getHarvestId(), $this->getClass(), $this->getProcessedCount());

		return $this->getProcessedCount();
	}

	public function getDataSource()
	{
		return Springbot_Boss::SOURCE_BULK_HARVEST;
	}

	public static function limitCollection($collection, Springbot_Util_Partition $partition, $id = 'entity_id')
	{
		if($partition->start) {
			$collection->addFieldToFilter($id, array('gteq' => $partition->start));
		}

		if($partition->stop) {
			$collection->addFieldToFilter($id, array('lteq' => $partition->stop));
		}
		return $collection;
	}
}
