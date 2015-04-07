<?php

class Springbot_Services_Harvest_Guests extends Springbot_Services_Harvest
{
	public function run()
	{
		$collection = $this->getCollection($this->getStoreId());
		$api = Mage::getModel('combine/api');
		$harvester = new Springbot_Combine_Model_Harvest_Guests($api, $collection, $this->getDataSource());
		$harvester->harvest();

		return $this->reportCount($harvester);
	}

	public function getCollection($storeId, $partition = null)
	{
		$collection = Mage::getModel('sales/order')->getCollection();
		$collection->addFieldToFilter('store_id', $storeId);
		$collection->addFieldToFilter('customer_is_guest', true);

		if ($this->getStartId() !== null) {
			$collection->addFieldToFilter('entity_id', array('gt' => $this->getStartId()));
		}
		if ($this->getStopId()) {
			$collection->addFieldToFilter('entity_id', array('lteq' => $this->getStopId()));
		}
		if ($partition) {
			$collection = parent::limitCollection($collection, $partition);
		}

		if (method_exists($collection, 'groupByAttribute')) {
			// Magento 1.3.*
			$collection->groupByAttribute('customer_email');
		}
		else if($collection->getSelect() instanceof Zend_Db_Select) {
			// Deduplicate by customer email
			try {
				$collection->getSelect()->order('increment_id')->group('customer_email');
			}
			catch (Exception $e) { }
		}

		return $collection;
	}
}
