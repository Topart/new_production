<?php

class Springbot_Bmbleb_RegisterController extends Mage_Adminhtml_Controller_Action
{

	protected function _initAction()
	{
		$domain = Mage::getBaseUrl();
		Mage::helper('bmbleb/ExternalLogging')->visibility("Registration Controller initiated", '', $domain);

		$regStatus = Mage::getStoreConfig('springbot/config/registration_status', Mage::app()->getStore());

		if ($regStatus == 'complete') {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('bmbleb')->__('Please register for your free Springbot account.'));
			session_write_close();
			$this->_redirect('bmbleb/adminhtml_index/status');
		}
		Mage::helper('bmbleb/ExternalLogging')->visibility("Null Registration detected", '', $domain);

		$this->loadLayout()
			->_setActiveMenu('bmbleb/register');

		return $this;
	}

	public function indexAction()
	{
		$this->_forward('edit');
	}

	public function editAction()
	{

		$this->_initAction();
		$this->loadLayout();
		$this->_addLeft(
			$this->getLayout()->createBlock('adminhtml/template')
				->setTemplate('bmbleb/tabs.phtml'));

		$this->_addContent($this->getLayout()->createBlock('bmbleb/adminhtml_bmbleb_register'));
		$this->renderLayout();
	}

	public function newAction()
	{
		$this->_forward('edit');
	}

	public function registerAction()
	{

		$emptyString = 0;
		$minimumNAMELength = 3;
		$minimumPASSWORDLength = 6;
		$validationMessage = '';

		$uname = $this->getRequest()->getParam('uname');
		$email = $this->getRequest()->getParam('email');
		$password = $this->getRequest()->getParam('password');
		$passwordverify = $this->getRequest()->getParam('passwordverify');

		if (strlen($uname) == $emptyString) {
			$validationMessage = $this->saveMessage($validationMessage, 'Your name is required');
		}
		if (strlen($email) == $emptyString) {
			$validationMessage = $this->saveMessage($validationMessage, 'Email is required.');
		}
		if (strlen($password) == $emptyString) {
			$validationMessage = $this->saveMessage($validationMessage, 'Password is required.');
		}
		if (strlen($passwordverify) == $emptyString) {
			$validationMessage = $this->saveMessage($validationMessage, 'Confirm Password is required.');
		}
		if (strlen($uname) < $minimumNAMELength) {
			$validationMessage = $this->saveMessage($validationMessage, "Your name must be at least $minimumNAMELength characters long.");
		}
		if (!$this->check_email_address($email)) {
			$validationMessage = $this->saveMessage($validationMessage, 'Email is not valid.');
		}
		if (strlen($password) < $minimumPASSWORDLength) {
			$validationMessage = $this->saveMessage($validationMessage, "Your password must be at least $minimumPASSWORDLength characters long.");
		}
		if ($password != $passwordverify) {
			$validationMessage = $this->saveMessage($validationMessage, 'Your passwords must match.');
		}
		if ($validationMessage != '') {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('bmbleb')->__($validationMessage));
			$this->_redirect('bmbleb/adminhtml_index/auth');
			return;
		}
		$payload = array();
		$payload['registeremail'] = $email;
		$payload['registerpassword'] = $password;
		$payload['verifypassword'] = $passwordverify;
		$payload['uname'] = $uname;

		$apiResponse = Mage::helper('bmbleb/ApiCall')->call("registration", $payload);
		if ($apiResponse->getResponsecode() == "200") {
			$msg = $this->parseBody($apiResponse->getFullresponse());
			if ($msg != '') {
				Mage::getSingleton('adminhtml/session')->addError(Mage::helper('bmbleb')->__($msg));
				$this->_redirect('bmbleb/adminhtml_index/auth');
				return;
			}
			Mage::getSingleton('core/session')->setBmblebJustRegistered(TRUE);
			Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('bmbleb')->__('Your Springbot Connect account has been created.'));

			$this->globalSettings($email, $password);
			$this->registerAllStores($email, $password);

			$this->_redirect('bmbleb/adminhtml_index/index');

		} else {

			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('bmbleb')->__('We\'re sorry, there\'s been an error: '
				. '[' . $apiResponse->getResponsecode() . ']'
				. '-' . $apiResponse->getMessage()));
			$this->_redirect('bmbleb/adminhtml_index/auth');
		}

	}

	function parseBody($body)
	{
		$msg = '';
		$pos = strpos($body, '"status":"error"');
		if ($pos > 0) {
			$len = strlen($body);
			$pos = strpos($body, '"message"', $pos);
			$pos = $pos + 12;
			$siz = $len - $pos - 3;
			$msg = substr($body, $pos, $siz);
		}
		return $msg;
	}

	function saveMessage($msg, $newmsg)
	{
		if ($msg == '') {
			return $newmsg;
		} else {
			return $msg;
		}
	}

	function check_email_address($email)
	{
		if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return true;
		} else {
			return false;
		}
	}

	function registerAllStores($email, $password)
	{
		Mage::app()->getWebsites();
		Mage::app()->getStores();

		foreach (Mage::app()->getWebsites() as $website) {
			foreach ($website->getGroups() as $group) {
				$stores = $group->getStores();
				foreach ($stores as $store) {
					$this->registerEachStore($email,
						$password,
						$store['website_id'],
						$store['store_id'],
						$store['name'],
						$store['code'],
						$store['is_active']);
				}
			}
		}
		$this->commitVars(array('registration_status' => 'complete'));
	}

	function registerEachStore($email, $pswd, $webId, $storeId, $storeName, $storeCode, $storeActive)
	{
		$logMsg = '>WebID->' . $webId
			. ' StoreID->' . $storeId
			. ' StoreName->' . $storeName
			. ' StoreCode->' . $storeCode
			. ' StoreActive->' . $storeActive;

		Mage::log('Register Store:' . $logMsg);

		$apiClass = 'stores';
		$baseUrl = Mage::getBaseUrl();
		$logo_src = Mage::getStoreConfig('design/header/logo_src');
		$logo_alt_tag = Mage::getStoreConfig('design/header/logo_alt');
		$media_url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);

		$url_components = explode('/', $baseUrl);
		$siteBaseURL = $url_components[0] . '//' . $url_components[2];

		$results = $this->RequestSecurityToken($email, $pswd);

		if ($results['status'] == 'error') {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('bmbleb')->__("Error: " . $results['message']));
			$this->_redirect('bmbleb/adminhtml_index/auth');
			return;
		}

		if ($results['status'] == 'ok') {
			$securityToken = $results['token'];
			$charid = strtoupper(md5(uniqid(rand(), true)));
			$guid = substr($charid, 0, 8) . '-'
				. substr($charid, 8, 4) . '-'
				. substr($charid, 12, 4) . '-'
				. substr($charid, 16, 4) . '-'
				. substr($charid, 20, 12);
			$stores = array();
			$stores['guid'] = $guid;
			$stores['url'] = $siteBaseURL;
			$stores['name'] = $storeName;
			//ENABLE: In next release (after 1/19)
			//
			//	  $stores['logo_link']		=$media_url.'/'.$logo_src;
			//	  $stores['logo_alt_tag']	=$logo_alt_tag;

			$attributes = array();
			$attributes['web_id'] = $webId;
			$attributes['store_id'] = $storeId;
			$attributes['store_name'] = $storeName;
			$attributes['store_code'] = $storeCode;
			$attributes['store_active'] = $storeActive;
			$attributes['store_url'] = $siteBaseURL;
			$stores['json_data'] = $this->formatJSON('', $attributes);
			$rawData = '{"' . $apiClass . '": {"' . $guid . '":'
				. $this->formatJSON('', $stores)
				. '}}';
			Mage::log('Register this store->' . $stores['json_data']);

			$apiModel = Mage::getModel('combine/api');
			$apiUrl = $apiModel->getApiUrl('stores');
			$ch = curl_init($apiUrl);
			$options = array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTPHEADER => array('Content-type: application/json', 'X-AUTH-TOKEN:' . $securityToken),
				CURLOPT_POSTFIELDS => $rawData
			);
			curl_setopt_array($ch, $options);
			$buffer = curl_exec($ch);

			$response = json_decode($buffer, true);
			if ($response['status'] == 'ok') {
				$springbot_storeId = array_search($guid, $response['stores']);
				$vars = array(
					'store_guid' => $guid,
					'store_id' => $springbot_storeId,
					'product_cursor' => '0',
					'category_cursor' => '0',
					'security_token' => $securityToken
				);
				$this->commitVars($vars, $storeId);
			}
		}
	}

	function globalSettings($email, $password)
	{
		$encyptPswd = Mage::helper('core')->encrypt($password);

		$vars = array(
			'initial_sync_inflight' => 'true',
			'registration_status' => 'new',
			'customer_sequence' => '5000000',
			'purchase_cursor' => '0',
			'account_email' => $email,
			'prev_account_email' => $email,
			'account_password' => $encyptPswd
		);

		$this->commitVars($vars);

		return;
	}

	function commitVars($vars, $storeID = '')
	{
		$config = new Mage_Core_Model_Config();
		foreach ($vars as $key => $val) {
			$config->saveConfig($this->makeConfigKey($key, $storeID), $val, 'default', 0);
		}
		return;
	}

	function makeConfigKey($dataClass, $storeId = '')
	{
		$cKey = 'springbot/config/' . $dataClass;

		if ($storeId != '') {
			$cKey = $cKey . '_' . $storeId;
		}
		return $cKey;
	}

	function RequestSecurityToken($email, $pswd)
	{
		$header = array('Content-type: application/json');
		$rawBuffer = '{"user_id":"' . $email . '", "password":"' . $pswd . '"}';
		Mage::log('Login ' . $rawBuffer);

		$apiModel = Mage::getModel('combine/api');
		$apiUrl = $apiModel->getApiUrl('registration/login');
		$ch = curl_init($apiUrl);
		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => $header,
			CURLOPT_POSTFIELDS => $rawBuffer
		);
		curl_setopt_array($ch, $options);
		$rs = curl_exec($ch);
		curl_close($ch);

		return json_decode($rs, true);

	}

	function formatJSON($dataID, $content)
	{
		$prefixLength = 0;;
		$howMany = sizeof($content);
		$pairCount = 0;
		$closeArray = '';

		if ($dataID != '') {
			$json = '"' . $content[$dataID] . '":{';
		} else {
			$json = '{';
			$closeArray = '}';
		}
		$dlm = '"';

		foreach ($content as $key => $value) {
			$dlm = '"';
			$keySize = strlen($key);
			$keyNoPrefix = substr($key, $prefixLength, $keySize - $prefixLength);
			if ($keyNoPrefix == 'json_data' || $keyNoPrefix == 'line_items') {
				$dlm = '';
			}
			$json = $json . '"' . $keyNoPrefix . '":' . $dlm . $value . $dlm;
			$pairCount++;
			if ($pairCount < $howMany) {
				$json = $json . ',';
			}
		}
		return $json . $closeArray;
	}
}
