<?php

class Springbot_Combine_Model_Redirect extends Springbot_Combine_Model_Abstract
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
}
