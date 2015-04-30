<?php
class Springbot_Bmbleb_Adminhtml_SettingsController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
	{
		$securityToken = Mage::getStoreConfig('springbot/config/security_token');
		if (!$securityToken) {
			$auth = Mage::helper('bmbleb/Account')->authenticate(
				Mage::getStoreConfig('springbot/config/account_email'),
				Mage::helper('core')->decrypt(Mage::getStoreConfig('springbot/config/account_password'))
			);
		} else {
			$auth = true;
		}

		if ($auth) {
			$bmbAcct = Mage::helper('bmbleb/Account');
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
			if ($password != '' || $passwordverify != '') {
				// some extra validation
				if (strlen($password) <= 6) {
					Mage::getSingleton('adminhtml/session')->addError('Passwords must be more than 6 characters long.');
				} else if ($password != $passwordverify) {
					Mage::getSingleton('adminhtml/session')->addError('The passwords entered did not match.');
				} else {
					// validated - attempt save
					$result = Mage::helper('bmbleb/ChangePassword')->ChangePassword($password);
					if ($result === true) {
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

}
