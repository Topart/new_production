<?php

class Springbot_Combine_Model_Redirect extends Mage_Core_Model_Abstract
{
	public function _construct()
	{
		$this->_init('combine/redirect');
		Mage::helper('combine/redirect')->checkAllRedirectTables();
	}

	public function save()
	{
		if($this->_validate()) {
			Springbot_Log::debug("Save redirect id : {$this->getRedirectId()} for order : {$this->getOrderId()}");
			parent::save();
		}
	}

	protected function _validate()
	{
		return $this->hasRedirectId() && !empty($this->_data['redirect_id']);
	}

	public function getAttributionIds()
	{
		$collection = Mage::getModel('combine/redirect')->getCollection()->loadByEmail($this->getEmail());
		$ids = $collection->getAllIds();
		return $ids;
	}

	/**
	 * Insert ignore into collection
	 */
	public function insertIgnore()
	{
		try {
			if($this->_validate()) {
				$this->_getResource()->insertIgnore($this);
			}
		} catch(Exception $e) {
			$this->_getResource()->rollBack();
			Springbot_Log::error($e);
		}

		return $this;
	}
}
