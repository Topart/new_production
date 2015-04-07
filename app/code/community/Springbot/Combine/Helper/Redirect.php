<?php

class Springbot_Combine_Helper_Redirect extends Mage_Core_Helper_Abstract
{

	public function insertRedirectIds($params, $ids = null)
	{
		if(is_null($ids)) {
			$ids = $this->getRedirectIds();
		} else if (is_string($ids)) {
			$ids = $this->getRedirectIds($ids);
		}

		foreach($ids as $id) {
			Springbot_Log::debug("Insert redirect_id : $id");
			$redirect = Mage::getModel('combine/redirect');

			$redirect->setData($params)
				->setRedirectId($id)
				->insertIgnore();
		}
	}

	public function checkAllRedirectTables()
	{
		$resource = Mage::getSingleton('core/resource');
		$tables = array(
			$resource->getTableName('combine/redirect'),
			$resource->getTableName('combine/redirect_order'),
		);

		foreach($tables as $table) {
			if($this->checkTable($table)) {
				return;
			}
		}
	}

	public function checkTable($table)
	{
		if(!Mage::getSingleton('core/resource')->getConnection('core_read')->showTableStatus($table)) {
			Springbot_Log::error("{$table} table does not exist. Rerunning Springbot update 1.0.0.70->1.2.0.0.");
			$setup = new Springbot_Combine_Model_Resource_Setup('combine_setup');
			$setup->reinstallSetupScript('1.0.0.70', '1.2.0.0');
			return true;
		}
	}

	public function getRedirectIds($raw = null)
	{
		if(is_null($raw)) {
			$raw = $this->getRawCookie();
		}
		$queue = explode($this->determineDelimiter($raw), trim($raw));
		return $this->sanitizeMongo($queue);
	}

	public function getLastId()
	{
		$ids = $this->getRedirectIds();
		if(count($ids)) {
			return $ids[0];
		}
	}

	public function sanitizeMongo($array)
	{
		$output = array();
		foreach($array as $value) {
			if(empty($value)) { continue; }
			if(preg_match("/^[0-9a-fA-F]{24}$/", $value)) {
				$output[] = $value;
			} else {
				$ip = Mage::helper('core/http')->getRemoteAddr();
				Springbot_Log::error(new Exception("{$value} attempted to pass as cookie param from {$ip}. Possible insertion attack."));
				Springbot_Boss::setCookie(Springbot_Boss::COOKIE_NAME, '');
			}
		}
		return $output;
	}

	public function encodeEscapeCookie($array)
	{
		return Mage::helper('combine')->escapeShell(implode('%7', $array));
	}

	public function getRedirectsByEmail($email, $dateLimit = null)
	{
		$collection = Mage::getModel('combine/redirect')
			->getCollection()
			->loadByEmail($email);
		$collection->getSelect()->order('id ASC');

		if (!is_null($dateLimit)) {
			$collection->addFieldToFilter('created_at', array('to' => $dateLimit));
		}

		if($collection instanceof Varien_Data_Collection && $collection->getSize() > 0) {
			return array_values($collection->getColumnValues('redirect_id'));
		}
		else {
			return array();
		}
	}

	public function getRedirectByOrderId($orderId)
	{
		$collection = Mage::getModel('combine/redirect')->getCollection()
			->joinOrderIds()
			->addFieldToFilter('order_id', $orderId)
			;
		$collection->getSelect()->order('id DESC');
		return $collection->getFirstItem();
	}

	public function getRawCookie()
	{
		return Mage::getModel('core/cookie')->get(Springbot_Boss::COOKIE_NAME);
	}

	public function hasRedirectId()
	{
		$raw = $this->getRawCookie();
		return !empty($raw);
	}

	public function determineDelimiter($str)
	{
		if (substr_count($str,'%7') > 0) {
			return '%7';
		} else {
			return '|';
		}
	}
}
