<?php

class Springbot_Combine_Model_Resource_Redirect_Order extends Springbot_Combine_Model_Resource_Abstract
{
	public function _construct()
	{
		$this->_init('combine/redirect_order', 'id');
		Mage::helper('combine/redirect')->checkTable($this->getMainTable());
	}
}
