<?php

class Springbot_Combine_Model_Redirect_Order extends Springbot_Combine_Model_Abstract
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
}
