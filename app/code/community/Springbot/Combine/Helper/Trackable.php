<?php

class Springbot_Combine_Helper_Trackable extends Mage_Core_Helper_Abstract
{

	public function getTrackables()
	{
		$sbCookie = $this->getCookie();
		return json_decode(base64_decode($sbCookie));
	}

	public function updateTrackables($order)
	{
		$quoteId = $order->getQuoteId();

		foreach($this->getTrackablesForQuote($quoteId) as $trackable) {
			$trackable->setOrderId($order->getId())
				->setCustomerId($order->getCustomerId())
				->save();
		}
	}

	public function hasTrackables()
	{
		$sb = $this->getCookie();
		return !empty($sb);
	}

	public function getCookie()
	{
		return Mage::getModel('core/cookie')->get(Springbot_Boss::SB_TRACKABLES_COOKIE);
	}

	public function getTrackablesHashByOrder($orderId)
	{
		$collection = $this->getTrackablesForQuote($order->getQuote->getId());
		return $this->_buildHash($collection);
	}

	public function getTrackablesHashByQuote($quoteId)
	{
		$collection = $this->getTrackablesForQuote($quoteId);
		return $this->_buildHash($collection);
	}

	public function getTrackablesForQuote($quoteId)
	{
		return Mage::getModel('combine/trackable')->getCollection()
			->addFieldToFilter('quote_id', $quoteId);
	}

	protected function _buildHash($collection)
	{
		$hash = new stdClass();
		foreach ($collection as $item) {
			$hash->{$item->getType()} = $item->getValue();
		}
		if (count((array) $hash) > 0) {
			return $hash;
		}
	}

}
