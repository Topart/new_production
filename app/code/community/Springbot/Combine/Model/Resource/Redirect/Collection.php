<?php

class Springbot_Combine_Model_Resource_Redirect_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
	public function _construct()
	{
		$this->_init('combine/redirect');
	}

	protected function _initSelect()
	{
		parent::_initSelect();
		Mage::helper('combine/redirect')->checkAllRedirectTables();
	}

	public function loadByEmail($email)
	{
		$this->addFieldToFilter('email', $email);
		return $this;
	}

	public function loadByQuoteId($quoteId)
	{
		$this->addFieldToFilter('quote_id', $quoteId);
		return $this;
	}

	public function loadByKey($email, $redirectId)
	{
		Springbot_Log::debug("Loading redirect for unique key {$email} : {$redirectId}");
		return $this->addFieldToFilter('email', $email)
			->addFieldToFilter('redirect_id', $redirectId)
			->getFirstItem();
	}

	public function joinOrderIds()
	{
		$this->getSelect()->join(
			array('at_order_id' => $this->getTable('combine/redirect_order')),
			'main_table.id = at_order_id.redirect_entity_id',
			'at_order_id.order_id'
		);
		return $this;
	}
}
