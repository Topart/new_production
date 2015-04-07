<?php

class Springbot_Services_Post_Purchase extends Springbot_Services_Post
{
	protected $_purchase;

	public function run()
	{
		$orderId = $this->getEntityId();
		Springbot_Log::debug('Executing Purchase Method (at '.date(Springbot_Boss::DATE_FORMAT).') Order Number->' . $orderId);

		$this->_purchase = Mage::getModel('sales/order')->load($orderId);
		$redirectIds = $this->_getRedirectIds();

		if(count($redirectIds)) {
			$this->_createRedirectForOrder($redirectIds[0]);

			$this->_purchase->setRedirectMongoId($redirectIds[0])
				->setRedirectMongoIds($redirectIds);
		}

		$api = Mage::getModel('combine/api');
		$collection = new Varien_Data_Collection;
		$harvester = new Springbot_Combine_Model_Harvest_Purchases($api, $collection, $this->getDataSource());

		$harvester->push($this->_purchase);
		$harvester->postSegment();

		if($this->_purchase->getCustomerIsGuest()) {
			Springbot_Boss::scheduleJob(
				'post:guest',
				array('i' => $orderId),
				Springbot_Services::LISTENER,
				'listener'
			);
		}
	}

	protected function _createRedirectForOrder($redirectId)
	{
		Springbot_Log::debug("Creating order entry for redirect_id: {$redirectId}");

		if($redirectId) {
			$redirect = Mage::getResourceModel('combine/redirect_collection')
				->loadByKey($this->_purchase->getCustomerEmail(), $redirectId);

			if($redirect) {
				$redirectOrder = Mage::getModel('combine/redirect_order');

				$redirectOrder->setData(array(
					'redirect_entity_id' => $redirect->getId(),
					'order_id' => $this->_purchase->getId(),
				));

				$redirectOrder->insertIgnore();
			}
		}
	}

	protected function _getRedirectIds()
	{
		return isset($this->_data['redirect_ids']) ? (array) $this->_data['redirect_ids'] : array();
	}
}
