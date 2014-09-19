<?php

class Springbot_Combine_Model_Resource_Redirect_Order_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
	public function _construct()
	{
		$this->_init('combine/redirect_order');
	}

	protected function _initSelect()
	{
		parent::_initSelect();
		Mage::helper('combine/redirect')->checkTable($this->getMainTable());
	}
}
