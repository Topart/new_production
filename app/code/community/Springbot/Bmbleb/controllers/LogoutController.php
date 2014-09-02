<?php
class Springbot_Bmbleb_LogoutController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
	{
		//$this->_forward('logout');
		return $this;
	}

	public function logoutAction()
	{
		$this->loadLayout();
		$this->_setActiveMenu('springbot_bmbleb');
		$this->renderLayout();
	}

	public function remoteDisconnectAction()
	{
		//Reset login status
		$bmblebAccount = Mage::helper('bmbleb/Account');
		$bmblebAccount->logout();
		$bmblebAccount->setSavedAccountInformation('', '');

		$this->_notifySpringbot();

		Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('bmbleb')->__('You have been successfully logged out of your Springbot Account.'));

		$this->_redirect('bmbleb/adminhtml_index/auth');
	}

	protected function _notifySpringbot()
	{
		$storeId = Mage::getStoreConfig('springbot/config/store_id_1');
		$payload = array(
			'type' => 'disconnect',
			'store_id' => $storeId,
			'store_url' => Mage::getUrl(),
			'description' => 'Admin user logged out of springbot!',
		);
		Mage::helper('bmbleb/externalLogging')->log($payload, $storeId);
		return;
	}
}
