<?php

class Springbot_Services_Post_Coupon extends Springbot_Services_Post
{
	public function run()
	{
		$coupon = Mage::getModel('salesrule/coupon');
		$coupon->load($this->getEntityId());
		$coupon->setStoreId($this->getStoreId());

		$api = Mage::getModel('combine/api');
		$collection = new Varien_Data_Collection;
		$harvester = new Springbot_Combine_Model_Harvest_Coupons($api, $collection, $this->getDataSource());
		$harvester->push($coupon);
		$harvester->postSegment();
	}
}

