<?php

class Springbot_BoneCollector_Model_HarvestCustomer_Observer extends Springbot_BoneCollector_Model_HarvestAbstract
{
	protected $_customer;

	public function onCustomerSaveAfter($observer)
	{
		try {
			$this->_initObserver($observer);
			$this->_customer = $observer->getEvent()->getCustomer();

			if ($this->_entityChanged($this->_customer)) {
				$customerId = $this->_customer->getId();
				Springbot_Boss::scheduleJob('post:customer', array('i' => $customerId), Springbot_Services::LISTENER, 'listener');
			}
		} catch (Exception $e) {
			Springbot_Log::error($e);
		}
	}

	public function onCustomerDeleteBefore($observer)
	{
		try {
			// Runs blocking in session to guarantee record existence
			$customer = $observer->getEvent()->getCustomer();
			$this->_initObserver($observer);
			Mage::getModel('Springbot_Services_Post_Customer')->setData(array(
				'start_id' => $customer->getId(),
				'delete' => true,
			))->run();
		} catch (Exception $e) {
			Springbot_Log::error($e);
		}
	}

	protected function _getAttributesToListenFor($extras = array())
	{
		$codes = array();
		$h = Mage::helper('combine/attributes');
		$attributes = $h->getCustomerCustomAttributes($h->getCustomerAttributeSet());

		try {
			foreach($attributes as $attribute) {
				$codes[] = $attribute->getAttributeCode();
			}
		}
		catch  (Exception $e) {
			Springbot_Log::debug('Exception caught during attribute iteration for customer observer');
		}


		// Ensure we test for change in group
		$codes[] = 'group_id';

		return parent::_getAttributesToListenFor($codes);
	}
}

