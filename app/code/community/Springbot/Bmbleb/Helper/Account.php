<?php

/**
 * manage account functions for a session
 * authenticate checks if email / password are valid and retrieves account
 * authorize checks to see if account has permission to perform certain functions
 * all other methods are private/protected
 */
class Springbot_Bmbleb_Helper_Account extends Mage_Core_Helper_Abstract
{
	public function setAccount($account = array())
	{
		Mage::getSingleton('core/session')->setBmblebAccount($account);
	}

	public function getAccount()
	{
		return Mage::getSingleton('core/session')->getBmblebAccount();
	}

	public function setIsLoggedIn($value)
	{
		return Mage::getSingleton('core/session')->setBmblebIsloggedIn($value);
	}

	public function getIsLoggedIn()
	{
		return Mage::getSingleton('core/session')->getBmblebIsloggedIn();
	}

	public function getIsAuthenticated()
	{
		$email = $this->getSavedEmail();
		$password = $this->getSavedPassword();

		if($email && $password) {
			$account = Mage::helper('combine')->checkCredentials($email, $password);
			if($account['valid']) {
				return true;
			} else if(isset($account['message'])) {
				Mage::getSingleton('adminhtml/session')->addError($account['message']);
			}
		}
		return false;
	}

	public function authenticate($email = '', $password = '')
	{
		$result = false;

		try {
			$saveCredentials = true;
			if ($email == '' && $password == ''){
				return false;
			}
			$account = Mage::helper('combine')->checkCredentials($email, $password);
			if ($account['valid']) {
				$result = true;
				if ($saveCredentials){
					$this->setSavedAccountInformation($email, $password);
				}
			} else {
				$this->setSavedAccountInformation('', '');
			}
		}
		catch (Mage_Core_Exception $e) {
			throw $e;
		}

		return $result;
	}

	public function authorize($feature = '')
	{
		if ($this->getIsLoggedIn() || $feature == ''){
			return true;
		} else {
			return false;
		}
	}

	public function logout()
	{
		$this->setAccount(array());
		$this->setIsLoggedIn(false);
	}

	public function getSavedEmail()
	{
		return Mage::getStoreConfig('springbot/config/account_email', Mage::app()->getStore());
	}

	public function getSavedPassword()
	{
		return Mage::helper('core')->decrypt(Mage::getStoreConfig('springbot/config/account_password', Mage::app()->getStore()));
	}

	public function getSavedSecurityToken()
	{
		return Mage::getStoreConfig('springbot/config/security_token', Mage::app()->getStore());
	}

	public function setSavedAccountInformation($email='', $password='', $secToken='')
	{
		$config = new Mage_Core_Model_Config();
		$config->saveConfig('springbot/config/account_email', $email, 'default', 0);
		$config->saveConfig('springbot/config/account_password', Mage::helper('core')->encrypt($password), 'default', 0);
		$config->saveConfig('springbot/config/security_token', $secToken, 'default', 0);
		Mage::getConfig()->cleanCache();
		Mage::getConfig()->reinit();
	}
}
