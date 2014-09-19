<?php
class Springbot_Bmbleb_LoginController extends Mage_Adminhtml_Controller_Action
{
    const CONFIG_VAR_FAMILY			   ="springbot";
	const CONFIG_VAR_GROUP			   ="config";
	private $configVars				   =array();

	protected function _initAction() {
		$this->loadLayout();
		return $this;
	}
	public function indexAction() {
      //  $this->_forward('edit');
		      $this->loadLayout()
      			->_addContent($this->getLayout()->createBlock('bmbleb/adminhtml_bmbleb_login'))
      			->renderLayout();
	}
    public function newAction() {
     //   $this->_forward('edit');
			      $this->loadLayout()
      			->_addContent($this->getLayout()->createBlock('bmbleb/adminhtml_bmbleb_login'))
      			->renderLayout();
    }
	public function editAction() {
        $this->loadLayout()
      			->_addContent($this->getLayout()->createBlock('bmbleb/adminhtml_bmbleb_login'))
      			->renderLayout();
    }
    public function loginAction() {

        $email 					  = $this->getRequest()->getParam('email');
        $pass 					  = $this->getRequest()->getParam('password');

        $bmblebAccount = Mage::helper('bmbleb/Account');
        $bmblebAccount->setIsLoggedIn(false);
        $url = $this->fetchConfigVariable('api_url','https://api.springbot.com/').'api/registration/login';

		try {
			$client = new Varien_Http_Client($url);
			$client->setRawData('{"user_id":"'.$email.'", "password":"'.$pass.'"}');
			$response 	= $client->request('POST');
			$result		= json_decode($response->getBody(),true);
		} catch (Exception $e) {
			Mage::log('Remote Springbot service unavailable!');
			Mage::logException($e);
			Mage::getSingleton('adminhtml/session')->addError('Service unavailable from ' . $url . ' please contact support@springbot.com.');
			$this->_redirect('bmbleb/adminhtml_index/auth');
			return;
		}

		if ($result['status']=='error') {
			Mage::getSingleton('adminhtml/session')->addError($result['message'].' or service unavailable from '.$url);
			$this->_redirect('bmbleb/adminhtml_index/auth');
		} else {
			if ($result['token']=='') {
				Mage::getSingleton('adminhtml/session')->addError('Login denied by Springbot');
				$this->_redirect('bmbleb/adminhtml_index/auth');
			} else {
				Mage::log('Email->'.$email.' Token->'.$result['token']);
				$bmblebAccount->setSavedAccountInformation($email,$pass,$result['token']);
				$this->_redirect('bmbleb/adminhtml_index/index');
			}
		}
    }
	private function fetchConfigVariable($varName,$default_value='')
	{
	 	$this->configVars = Mage::getStoreConfig(self::CONFIG_VAR_FAMILY.'/'.self::CONFIG_VAR_GROUP, Mage::app()->getStore());

	    if (isset($this->configVars[$varName])) {
		 	  	$rtnValue  = $this->configVars[$varName];
		} else {
		   		$rtnValue = $default_value;
		}
		return $rtnValue;
	}
}
