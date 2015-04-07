<?php
class Springbot_Bmbleb_LoginController extends Mage_Adminhtml_Controller_Action
{
	protected function _initAction()
	{
		$this->loadLayout();
		return $this;
	}

	public function indexAction()
	{
		$this->loadLayout()
			->_addContent($this->getLayout()->createBlock('bmbleb/adminhtml_bmbleb_login'))
			->renderLayout();
	}

    public function newAction()
	{
		$this->loadLayout()
			->_addContent($this->getLayout()->createBlock('bmbleb/adminhtml_bmbleb_login'))
			->renderLayout();
    }

	public function editAction()
	{
        $this->loadLayout()
			->_addContent($this->getLayout()->createBlock('bmbleb/adminhtml_bmbleb_login'))
			->renderLayout();
    }

    public function loginAction()
	{
        $email = $this->getRequest()->getParam('email');
        $pass = $this->getRequest()->getParam('password');

        $bmblebAccount = Mage::helper('bmbleb/Account');
        $bmblebAccount->setIsLoggedIn(false);
		if (!($url = Mage::getStoreConfig('springbot/config/api_url'))) {
			$url = 'https://api.springbot.com/';
		}
        $url .= 'api/registration/login';

		try {
			$client = new Varien_Http_Client($url);
			$client->setRawData('{"user_id":"'.$email.'", "password":"'.$pass.'"}');
			$client->setHeaders('Content-type: application/json');
			$response = $client->request('POST');
			$result   = json_decode($response->getBody(),true);
		}
		catch (Exception $e) {
			Springbot_Log::error($e);
			Mage::getSingleton('adminhtml/session')->addError('Service unavailable from ' . $url . ' please contact support@springbot.com.');
			$this->_redirect('bmbleb/adminhtml_index/auth');
			return;
		}

		if ($result['status']=='error') {
			Mage::getSingleton('adminhtml/session')->addError($result['message'].' or service unavailable from '.$url);
			$this->_redirect('bmbleb/adminhtml_index/auth');
		}
		else {
			if ($result['token'] == '') {
				Mage::getSingleton('adminhtml/session')->addError('Login denied by Springbot');
				$this->_redirect('bmbleb/adminhtml_index/auth');
			}
			else {
				$bmblebAccount->setSavedAccountInformation($email,$pass,$result['token']);
				$this->_redirect('bmbleb/adminhtml_index/index');
			}
		}
    }

}
