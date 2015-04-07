<?php

class Springbot_Services_Store_Register extends Springbot_Services
{
	const API_CLASS = 'stores';

	protected $_guid;

	public function run()
	{
		$guid = $this->_getGuid();

		$response = $this->_query($guid, $this->getStoreArray($guid));

		if ($response['status'] == 'ok' && isset($response['stores'])) {
			$springbotStoreId = array_search($guid, $response['stores']);
			$id = $this->getStoreId();
			$vars = array(
				'store_guid'     => $guid,
				'store_id'       => $springbotStoreId,
				'security_token' => $this->_getSecurityToken()
			);
			$this->commitVars($vars, $id);
			Mage::getConfig()->cleanCache();
		}
	}

	public function getStoreArray($guid)
	{
		$helper = $this->_getHelper();
		$storeUrl = $helper->getStoreUrl($this->getStoreId());
		$store = $this->_getStore();

		$storeDetail = array(
			'guid'         => $guid,
			'url'          => $storeUrl,
			'name'         => $store->getName(),
			'logo_src'     => Mage::getStoreConfig('design/header/logo_src'),
			'logo_alt_tag' => Mage::getStoreConfig('design/header/logo_alt'),
			'json_data'    => array(
				'web_id' => $store->getWebsiteId(),
				'store_id' =>  $this->getStoreId(),
				'store_name' => $store->getName(),
				'store_code' => $store->getCode(),
				'store_active' => $store->getIsActive(),
				'store_url' => $storeUrl,
				'media_url' => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA),
				'store_mail_address' => $this->_getStoreAddress(),
				'store_custsrv_email' => Mage::getStoreConfig('trans_email/ident_support/email'),
				'store_statuses' => $this->_getStoreStatuses($this->getStoreId())
			),
		);

		return $storeDetail;
	}

	public function commitVars($vars)
	{
		foreach ($vars as $key => $val)
		{
			$configKey = $this->_makeConfigKey($key, $this->getStoreId());
			Springbot_Log::harvest('Committing Config Var ['. $configKey .']->'.$val);
			Mage::getConfig()->saveConfig($configKey, $val, 'default', 0);
		}
	}

	protected function _getStoreStatuses($storeId) {
		return Mage::getModel('sales/order')->getConfig()->getStatuses();
	}

	protected function _getSecurityToken()
	{
		return Mage::helper('combine')->requestSecurityToken();
	}

	protected function _query($guid, $storeMetaData)
	{
		return Mage::helper('combine')->apiPostWrapped(self::API_CLASS, array($guid => $storeMetaData));
	}

	protected function _getGuid()
	{
		return Mage::helper('combine')->getStoreGuid($this->getStoreId());
	}

	protected function _getStore()
	{
		return Mage::getModel('core/store')->load($this->getStoreId());
	}

	protected function _getStoreAddress()
	{
		return str_replace(array("\n","\r"),"|",Mage::getStoreConfig('general/store_information/address'));
	}

	protected function _getHelper()
	{
		return Mage::helper('combine/harvest');
	}

	protected function _makeConfigKey($dataClass, $storeId = '')
	{
		$cKey = 'springbot/config/'.$dataClass;
		if ($storeId != '') {
			$cKey = $cKey.'_'.$storeId;
		}
		return $cKey;
	}
}
