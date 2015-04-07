<?php

abstract class Springbot_Services_Harvest extends Springbot_Services
{

	public function reportCount($harvester)
	{
		$mb = round(memory_get_peak_usage(true) / pow(1024, 2), 2);

		$processedCount = $harvester->getProcessedCount();
		$segmentMin = $harvester->getSegmentMin();
		$segmentMax = $harvester->getSegmentMax();
		$apiModel = $harvester->getApiModel();

		$msg = "{$apiModel} block {$segmentMin}:{$segmentMax} posted [{$processedCount} overall] | {$mb}MB | {$this->getRuntime()} sec";

		Springbot_Log::harvest($msg);
		$countObject = Mage::getModel('combine/cron_count');
		$countObject->increaseCount($this->getStoreId(), $this->getHarvestId(), $harvester->getApiModel(), $harvester->getProcessedCount());

		return $harvester->getProcessedCount();
	}

	public function getDataSource()
	{
		return Springbot_Boss::SOURCE_BULK_HARVEST;
	}

	public static function limitCollection($collection, Springbot_Util_Partition $partition, $idColumn = 'entity_id')
	{
		if ($partition->start) {
			$collection->addFieldToFilter($idColumn, array('gteq' => $partition->start));
		}

		if ($partition->stop) {
			$collection->addFieldToFilter($idColumn, array('lteq' => $partition->stop));
		}
		return $collection;
	}
}
