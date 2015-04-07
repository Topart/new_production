<?php

class Springbot_BoneCollector_Model_HarvestAbstract
{
	/**
	 * The attributes which we want to listen for changes on
	 *
	 * @return array
	 */
	protected $_attributes = array();

	protected function _initObserver($observer)
	{
		if($event = $observer->getEvent()) {
			Springbot_Log::debug($event->getName());
		}
	}

	/**
	 * Get top level sku
	 *
	 * This aims to get the top level sku.  The getSku method for the product
	 * model is overloaded providing the type instance version of the sku
	 * meaning that it gives the simple sku for configurable or grouped products
	 * we need to get the _data array directly and pass that sku up to ensure the
	 * parent sku.
	 *
	 * @param $observer Varient_Event_Observer
	 * @return string
	 */
	public function getTopLevelSku($observer)
	{
		$product = $observer->getEvent()->getProduct();
		return Mage::helper('combine/parser')->getTopLevelSku($product);
	}

	public function doSend($object, $sessionKey)
	{
		$json    = $object->toJson();
		$hash    = sha1($json);
		$session = $this->_getSession();

		if ($session->getData($sessionKey) == $hash) {
			Springbot_Log::debug("Hash for {$sessionKey} is match, this object has already been posted, skipping");
			return false;
		} else {
			$session->setData($sessionKey, $hash);
			Springbot_Log::debug("Hash for {$sessionKey} does not match cache, sending");
			return true;
		}
	}

	protected function _getSession()
	{
		return Mage::getSingleton('core/session');
	}

	protected function _getAttributesToListenFor($extras = array())
	{
		return array_merge($this->_attributes, $extras);
	}

	protected function _entityChanged($model)
	{
		foreach($this->_getAttributesToListenFor() as $attribute) {
			if($attribute != 'created_at' && $attribute != 'updated_at') {
				if($this->_hasDataChangedFor($model, $attribute)) {
					return true;
				}
			}
		}
		Springbot_Log::debug('Entity unchanged');
		return false;
	}

	protected function _hasDataChangedFor($model, $field)
	{
		$newData = $model->getData($field);
		$origData = $model->getOrigData($field);
		return $newData != $origData;
	}

}
