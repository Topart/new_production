<?php
class Springbot_Bmbleb_Adminhtml_SettingsController extends Mage_Adminhtml_Controller_Action
{
	protected $_configVars;

	const CONFIG_VAR_FAMILY        = 'springbot';
	const CONFIG_VAR_GROUP         = 'config';

	protected function _init()
	{
		$this->_configVars = Mage::getStoreConfig(self::CONFIG_VAR_FAMILY.'/'.self::CONFIG_VAR_GROUP,Mage::app()->getStore());
	}

	public function indexAction()
	{
		$this->_init();

		$securityToken = $this->fetchConfigVariable('security_token');

		if(empty($securityToken)) {
			$auth = Mage::helper('bmbleb/Account')->authenticate(
					$this->fetchConfigVariable('account_email'),
					Mage::helper('core')->decrypt($this->fetchConfigVariable('account_password'))
			);
		} else {
			$auth = true;
		}

		if ($auth) {
			$bmbAcct=Mage::helper('bmbleb/Account');
			$bmbAcct->setIsLoggedIn(true);
			$this->_redirect('bmbleb/adminhtml_index/status');
			return;
		}

		$this->_redirect('bmbleb/adminhtml_index/auth');
		return;
	}
	public function postAction()
	{
		if ($data = $this->getRequest()->getPost()) {
			// if both password fields are empty then do NOT attempt to update them
			$password = $data['password'];
			$passwordverify = $data['passwordverify'];
			if ($password != '' || $passwordverify != ''){
				// some extra validation
				if (strlen($password) <= 6){
					Mage::getSingleton('adminhtml/session')->addError('Passwords must be more than 6 characters long.');
				} else if ($password != $passwordverify){
					Mage::getSingleton('adminhtml/session')->addError('The passwords entered did not match.');
				} else {
					// validated - attempt save
					$result = Mage::helper('bmbleb/ChangePassword')->ChangePassword($password);
					if ($result === true){
						// update the saved and session password too
						$bmblebAccount = Mage::helper('bmbleb/Account');
						$account = $bmblebAccount->getAccount();
						$account['password'] = $password;
						$bmblebAccount->setAccount($account);
						$bmblebAccount->setSavedAccountInformation($account['email'], $password);

						Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Your password was successfully updated.'));
					} else {
						// $result contains the error message
						Mage::getSingleton('adminhtml/session')->addError(Mage::helper('bmbleb')->__('We\'re sorry, there\'s been an error. ') . $result);
					}
				}
			}
		} else {
			Mage::getSingleton('adminhtml/session')->addError('No data submitted');
		}
		$this->_redirect('*/*/index', array());
		return;
	}
	private function fetchConfigVariable($varName, $default_value = '')
	{
		if (isset($this->_configVars[$varName])) {
			return $this->_configVars[$varName];
		} else {
			return $default_value;
		}
	}
}
