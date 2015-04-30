<?php

class Springbot_Combine_Model_Cron extends Mage_Core_Model_Abstract
{
	protected function _validate()
	{
		return true;
	}

	public function insertIgnore()
	{
		try {
			if($this->_validate()) {
				$this->_getResource()->insertIgnore($this);
			}
		}
		catch(Exception $e) {
			$this->_getResource()->rollBack();
			Springbot_Log::error($e);
		}
		return $this;
	}


}
