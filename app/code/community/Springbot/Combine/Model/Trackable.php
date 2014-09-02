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
			if($this->getResource()->create($this)) {
			} else {
				$this->save();
			}
		}
		return $this;
	}

	protected function _validate()
	{
		return !empty($this->_data['email']) &&
			!empty($this->_data['type']) &&
			!empty($this->_data['value']) &&
			!empty($this->_data['quote_id']);
	}
}
