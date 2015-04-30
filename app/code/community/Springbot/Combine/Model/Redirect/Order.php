<?php

class Springbot_Combine_Model_Redirect_Order extends Mage_Core_Model_Abstract
{
	public function _construct()
	{
		$this->_init('combine/redirect_order');
		Mage::helper('combine/redirect')->checkTable($this->getMainTable());
	}

	protected function _validate()
	{
		$entity = $this->getRedirectEntityId();
		$orderId = $this->getOrderId();
		return !(empty($entity) || empty($orderId));
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
