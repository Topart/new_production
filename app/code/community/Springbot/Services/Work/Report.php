<?php

class Springbot_Services_Work_Report extends Springbot_Services
{
	public function run()
	{
		$keyUpper = ucwords($this->getClass());
		$countModel = Mage::getModel('combine/cron_count');
		$count = $countModel->getProcessedCount($this->getStoreId(), $this->getHarvestId(), $this->getClass());
		$springbotStoreId = Mage::helper('combine/harvest')->getSpringbotStoreId($this->getStoreId());
		$countModel->setCompletedTime($this->getStoreId(), $this->getHarvestId(), $this->getClass());
		$startTime = $countModel->getEntityStartTime($this->getStoreId(), $this->getHarvestId(), $this->getClass());
		$completedTime = $countModel->getEntityCompletedTime($this->getStoreId(), $this->getHarvestId(), $this->getClass());
		$params = array(
			'store_id' => $springbotStoreId,
			'type' => $keyUpper,
			'sent' => $count,
			'started' => $startTime,
			'completed' => $completedTime,
		);

		Mage::helper('combine/harvest')->reportHarvestCount($params, $this->getHarvestId());
		Springbot_Log::remote("Harvested {$count} {$keyUpper} from store " . $this->getStoreId(), $this->getStoreId());
	}

}
