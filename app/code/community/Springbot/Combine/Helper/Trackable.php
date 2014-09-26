<?php

class Springbot_Combine_Helper_Trackable extends Mage_Core_Helper_Abstract
{
	const SB_COOKIE = '_sbtk';

	public function getTrackables()
	{
		$sbCookie = $this->getCookie();
		return json_decode(base64_decode($sbCookie));
	}

	public function getCookie()
	{
		return Mage::getModel('core/cookie')->get(self::SB_COOKIE);
	}

	public function hasTrackables()
	{
		$sb = $this->getCookie();
		return !empty($sb);
	}

	public function addTrackable($customerEmail, $type, $value, $quoteId, $customerId) {
		$model = Mage::getModel('combine/trackable');
		$model->setData(
			array(
				'email' => $customerEmail,
				'type' => $type,
				'value' => $value,
				'quote_id' => $quoteId,
				'customer_id' => $customerId
			)
		);
		$model->createOrUpdate();
	}

	public function getTrackablesHashByOrder($orderId)
	{
		$collection = Mage::getModel('combine/trackable')->getCollection()
			->addFieldToFilter('order_id', $orderId);

		return $this->_buildHash($collection);
	}

	public function getTrackablesHashByQuote($quoteId)
	{
		$collection = Mage::getModel('combine/trackable')->getCollection()
			->addFieldToFilter('quote_id', $quoteId);

		return $this->_buildHash($collection);
	}

	protected function _buildHash($collection)
	{
		$hash = new stdClass();

		foreach($collection as $item) {
			$hash->{$item->getType()} = $item->getValue();
		}

		if(!Mage::helper('combine')->isEmpty($hash)) {
			return $hash;
		}
	}

}
