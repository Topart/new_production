<?php

class Springbot_Combine_Model_Trackable extends Mage_Core_Model_Abstract
{
	public function _construct()
	{
		$this->_init('combine/trackable');
	}

	public function createOrUpdate()
	{
		if($this->_validate()) {
			if (!$this->getResource()->create($this)) {
				$this->save();
			}
		}
		$trackables = $this->getTrackables();
		$this->_setSbTrackablesCookie($trackables);
		return $this;
	}

	protected function _validate()
	{
		return !empty($this->_data['type']) &&
			!empty($this->_data['value']) &&
			!empty($this->_data['quote_id']);
	}

	public function updateTrackables($order)
	{
		foreach ($this->getTrackablesForQuote($order->getId()) as $trackable) {
			$trackable->setOrderId($order->getId())
				->setCustomerId($order->getCustomerId())
				->save();
		}
	}

	public function getTrackablesForQuote($quoteId)
	{
		return Mage::getModel('combine/trackable')->getCollection()
			->addFieldToFilter('quote_id', $quoteId);
	}

	public function isObjectEmpty($obj)
	{
		return count((array) $obj) == 0;
	}

	private function _setSbTrackablesCookie($params)
	{
		if (!$this->isObjectEmpty($params)) {
			$encoded = base64_encode(json_encode($params));
			Springbot_Boss::setCookie(Springbot_Boss::SB_TRACKABLES_COOKIE, $encoded);
		}
	}

	public function getTrackables()
	{
		$params = Mage::app()->getRequest()->getParams();
		$origParams = Mage::helper('combine/trackable')->getTrackables();
		if ($origParams) {
			$sbParams = clone $origParams;
		}
		else {
			$sbParams = new stdClass();
		}
		foreach ($params as $param => $value) {
			if (preg_match('/^sb_/', $param)) {
				Springbot_Log::debug("Assigning $param from url with $value");
				$sbParams->$param = $value;
			}
		}
		return !$this->isObjectEmpty($sbParams) ? $sbParams : new stdClass();
	}




}
