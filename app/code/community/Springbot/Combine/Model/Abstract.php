<?php

class Springbot_Combine_Model_Abstract extends Mage_Core_Model_Abstract
{
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

	protected function _validate()
	{
		return true;
	}
}
