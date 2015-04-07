<?php

class Springbot_Combine_Model_Api extends Varien_Object
{
	const SUCCESSFUL_RESPONSE   = 'ok';
	const HTTP_CONTENT_TYPE     = 'Content-type: application/json';
	const TOTAL_POST_FAIL_LIMIT = 32;
	const RETRY_LIMIT           = 3;

	protected $_securityToken;
	protected $_client;
	protected $_header;
	protected $_url;
	protected $_retries = 0;
	protected $_requestStart;

	public function wrap($model, $data)
	{
		$transport = new stdClass();
		$transport->$model = $data;

		return Zend_Json::encode($transport,
			false,
			array('enableJsonExprFinder' => true)
		);
	}

	public function reinit()
	{
		$this->_retries = 0;
		return $this;
	}

	public function get($method, $param = array(), $authenticate = true)
	{
		$url = $method . '?' . http_build_query($param);
		return $this->call($url, false, $authenticate, Varien_Http_Client::GET);
	}

	public function put($method, $payload, $authenticate = true)
	{
		return $this->call($method, $payload, $authenticate, Varien_Http_Client::PUT);
	}

	public function call($method, $payload = false, $authenticate = true, $httpMethod = Varien_Http_Client::POST)
	{
		$result = array();
		$client = $this->getClient($httpMethod);
		$client->setUri($this->getApiUrl($method));
		Springbot_Log::debug("Calling Springbot api method : $method | " . $client->getUri(true));

		if($authenticate) {
			$this->authenticate();
			$client->setHeaders('X-AUTH-TOKEN:' . $this->_securityToken);
		}

		if($payload) {
			$client->setRawData(utf8_encode($payload));
		}

		try {
			// stop Zend_Http_Client from dumping to stream on error
			ob_start();
			$this->_startProfile();
			$response = $client->request();
			$this->_stopProfile();
			ob_end_clean();

			if($response->isSuccessful()) {
				$result = json_decode($response->getBody(),true);
				if ($result['status'] != self::SUCCESSFUL_RESPONSE) {
					Springbot_Log::harvest($response->getBody());
				}
			}
			Springbot_Log::http($payload);
		} catch (Exception $e) {
			Springbot_Log::error($e);
			$code = isset($result['status']) ? $result['status'] : 'null';
			throw new Exception("$method call failed with code: $code");
		}

		return $result;
	}


	public function hasToken()
	{
		$token = Mage::getStoreConfig('springbot/config/security_token');
		if($token) {
			$this->_securityToken = $token;
		}
		return isset($this->_securityToken);
	}

	public function authenticate()
	{
		if(!$this->hasToken()) {
			$credentials = $this->_getApiCredentials();
			$result = $this->call('registration/login', $credentials, false);

			if(!isset($result['token'])) {
				throw new Exception('Token not available in api response. Please check springbot credentials.');
			}

			$this->_securityToken = $result['token'];
		}
		return;
	}

	public function getApiUrl($method = '')
	{
		if(!isset($this->_url)) {
			$this->_url =  Mage::getStoreConfig('springbot/config/api_url');
			if(!$this->_url) {
				$this->_url = 'https://api.springbot.com/';
			}
			$this->_url .= 'api/';
		}
		return $this->_url . $method;
	}

	public function getClient($method = Varien_Http_Client::POST)
	{
		$this->_client = new Zend_Http_Client();
		$this->_client->setMethod($method);
		$this->_client->setHeaders(self::HTTP_CONTENT_TYPE);
		return $this->_client;
	}

	public function getLastStatus()
	{
		return $this->_responseCode;
	}

	protected function _getApiCredentials()
	{
		$post = array(
			'user_id' => $this->_getAccountEmail(),
			'password' => $this->_getAccountPassword(),
		);
		return json_encode($post);
	}

	protected function _getAccountEmail()
	{
		return Mage::getStoreConfig('springbot/config/account_email');
	}

	protected function _getAccountPassword()
	{
		$passwd = Mage::getStoreConfig('springbot/config/account_password');
		return Mage::helper('core')->decrypt($passwd);
	}

	protected function _getSecurityToken()
	{
		$this->_securityToken = Mage::getStoreConfig('springbot/config/security_token');
		return $this->_securityToken;
	}

	protected function _startProfile()
	{
		$this->_requestStart = Mage::helper('combine')->getMicroTime();
	}

	protected function _stopProfile()
	{
		$time = Mage::helper('combine')->getMicroTime() - $this->_requestStart;
		Springbot_Log::debug("Request completed in $time sec");
	}
}
