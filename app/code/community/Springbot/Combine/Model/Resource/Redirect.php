<?php

class Springbot_Combine_Model_Resource_Redirect extends Springbot_Combine_Model_Resource_Abstract
{
	protected $_redirectOrderTable;

	public function _construct()
	{
		$this->_init('combine/redirect', 'id');
		Mage::helper('combine/redirect')->checkAllRedirectTables();
		$this->_redirectOrderTable = $this->getTable('combine/redirect_order');
	}
}
