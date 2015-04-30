<?php

class Springbot_Services_Harvest_Coupons extends Springbot_Services_Harvest
{
	public function run()
	{
		$collection = $this->getCollection($this->getStoreId());
		$api = Mage::getModel('combine/api');
		$harvester = new Springbot_Combine_Model_Harvest_Coupons($api, $collection, $this->getDataSource());
		$harvester->harvest();
		$this->reportCount($harvester);
	}

	public function getCollection($storeId, $partition = null)
	{
		if(!mageFindClassFile('Mage_SalesRule_Model_Coupons')) {
			return array();
		}

		// Filter based on the website_ids string
		$collection = Mage::getModel('salesrule/coupon')->getCollection();

		if ($partition) {
			$collection = parent::limitCollection($collection, $partition, 'coupon_id');
		}
		if ($this->getStartId() !== null) {
			$collection->addFieldToFilter('coupon_id', array('gt' => $this->getStartId()));
		}
		if ($this->getStopId()) {
			$collection->addFieldToFilter('coupon_id', array('lteq' => $this->getStopId()));
		}

		return $collection;
	}
}
