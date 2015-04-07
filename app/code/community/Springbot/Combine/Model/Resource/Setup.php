<?php

class Springbot_Combine_Model_Resource_Setup extends Mage_Core_Model_Resource_Setup
{
	protected $_app;
	protected $_api;
	protected $_config;
	protected $_data = array();

	public function toJson($attributes = array())
	{
		$obj = new stdClass();
		$obj->installs = array($this->_data);
		return json_encode($obj);
	}

	public function reinstallSetupScript($fromVersion, $toVersion)
	{
		Mage::log("Reinstall $fromVersion to $toVersion");
		$files = $this->_getAvailableDbFiles(self::TYPE_DB_UPGRADE, $fromVersion, $toVersion);

		try {
			foreach($files as $file) {
				$fileName = $file['fileName'];
				Mage::log("Reinstall $fileName");
				$conn = $this->getConnection();
				include $fileName;
			}
		} catch (Exception $e) {
			Mage::logException($e);
		}
	}

	public function resendInstallLog()
	{
		$this->getSiteDetails();
		$this->submit();
	}

	public function getData()
	{
		return $this->_data;
	}

	public function getSiteDetails()
	{
		try{
			$this->fetchConfig();
		} catch (Exception $e) {
			Mage::logException($e);
			$this->_setData('type', 'magento')
				->_setData('error', 'General failure on install.');
		}
		return true;
	}

	public function fetchConfig()
	{
		$this->_getApp()->reinitStores();
		$config = $this->_getConfig();
		$config->getResourceModel()->loadToXml($config);

		$this->_setData('type', 'magento')
			->_setData('version', $this->getVersion())
			->_setData('primary_url', $this->getPrimaryUrl())
			->_setData('modules', $this->getExtensions())
			->_setData('store_details', $this->getStoreDetails())
			->_setData('system_info', $this->getSystemDetails());
		return $this;
	}

	public function submit()
	{
		$this->_getApi()->call('installs', $this->toJson(), false);
	}

	public function getVersion()
	{
		return Mage::getVersion();
	}

	public function getPrimaryUrl()
	{
		return $this->_getApp()
			->getStore(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID)
			->getConfig(Mage_Core_Model_Store::XML_PATH_UNSECURE_BASE_URL);
	}

	public function getStoreDetails()
	{
		$stores = array();
		foreach($this->_getStores() as $store) {
			if($store instanceof Mage_Core_Model_Store) {
				$stores[] = array(
					'id' => $store->getId(),
					'name' => $store->getName(),
					'base_url' => $store->getConfig(Mage_Core_Model_Store::XML_PATH_UNSECURE_BASE_URL),
					'transactional_emails' => $store->getConfig('trans_email'),
					'contact_information' => $store->getConfig('general/store_information'),
				);
			}
		}
		return $stores;
	}

	public function getSystemDetails()
	{
		return array(
			'php_version' => phpversion(),
			'host' => php_uname(),
			'php_exec_healthcheck' => $this->_checkPhpExec(),
			'functions_exist' => array (
				'system' => $this->_checkFunction('system'),
				'exec' => $this->_checkFunction('exec'),
				'escapeshellarg' => $this->_checkFunction('escapeshellarg'),
				'escapeshellcmd' => $this->_checkFunction('escapeshellcmd'),
				'passthru' => $this->_checkFunction('passthru'),
				'shell_exec' => $this->_checkFunction('shell_exec'),
				'proc' => array(
					'proc_close' => $this->_checkFunction('proc_close'),
					'proc_get_status' => $this->_checkFunction('proc_get_status'),
					'proc_nice' => $this->_checkFunction('proc_nice'),
					'proc_open' => $this->_checkFunction('proc_open'),
					'proc_terminate' => $this->_checkFunction('proc_terminate'),
				),
				'pcntl' => array(
					'pcntl_alarm' => $this->_checkFunction('pctnl_alarm'),
					'pcntl_errno' => $this->_checkFunction('pctnl_errno'),
					'pcntl_exec' => $this->_checkFunction('pctnl_exec'),
					'pcntl_fork' => $this->_checkFunction('pctnl_fork'),
					'pcntl_get_last_error' => $this->_checkFunction('pctnl_get_last_error'),
					'pcntl_getpriority' => $this->_checkFunction('pctnl_getpriority'),
					'pcntl_setpriority' => $this->_checkFunction('pctnl_setpriority'),
					'pcntl_signal_dispatch' => $this->_checkFunction('pctnl_signal_dispatch'),
					'pcntl_signal' => $this->_checkFunction('pctnl_signal'),
					'pcntl_sigprocmask' => $this->_checkFunction('pctnl_sigprocmask'),
					'pcntl_sigtimedwait' => $this->_checkFunction('pctnl_sigtimedwait'),
					'pcntl_sigwaitinfo' => $this->_checkFunction('pctnl_sigwaitinfo'),
					'pcntl_strerror' => $this->_checkFunction('pctnl_strerror'),
					'pcntl_wait' => $this->_checkFunction('pctnl_wait'),
					'pcntl_waitpid' => $this->_checkFunction('pctnl_waitpid'),
					'pcntl_wexitstatus' => $this->_checkFunction('pctnl_wexitstatus'),
					'pcntl_wifexited' => $this->_checkFunction('pctnl_wifexited'),
					'pcntl_wifsignaled' => $this->_checkFunction('pctnl_wifsignaled'),
					'pcntl_wifstopped' => $this->_checkFunction('pctnl_wifstopped'),
					'pcntl_wstopsig' => $this->_checkFunction('pctnl_wstopsig'),
					'pcntl_wtermsig' => $this->_checkFunction('pctnl_wtermsig'),
				)
			),
			'phpinfo' => $this->_phpinfoArray(true),
		);
	}

	public function getExtensions()
	{
		$versions = new stdClass();
		$modules = $this->_getConfig()->getNode('modules')->children();
		if($modules) {
			foreach($modules as $name => $meta) {
				if(strpos($name, 'Mage') !== 0) {
					$versions->$name = $meta;
				}
			}
		}
		return $versions;
	}

	protected function _setData($type, $value = null)
	{
		$this->_data[$type] = $value;
		return $this;
	}

	protected function _getApp()
	{
		if(!isset($this->_app)) {
			$this->_app = Mage::app();
		}
		return $this->_app;
	}

	protected function _getConfig()
	{
		if(!isset($this->_config)) {
			$this->_config = Mage::getConfig();
		}
		return $this->_config;
	}

	protected function _getStores()
	{
		return $this->_getApp()->getStores();
	}

	protected function _getApi()
	{
		if(!isset($this->_api)) {
			$this->_api = Mage::getModel('combine/api');
		}
		return $this->_api;
	}

	protected function _checkFunction($func)
	{
		return function_exists($func) ? 'true' : 'false';
	}

	protected function _checkPhpExec()
	{
		ob_start();
		$check = system("/usr/bin/php -r \"echo 'ok';\"");
		ob_end_clean();
		return $check == "ok" ? "ok" : "could not execute php as shell";
	}

	protected function _phpinfoArray($return=false){
		/* Andale!  Andale!  Yee-Hah! */
		ob_start();
		phpinfo(-1);

		$pi = preg_replace(
			array('#^.*<body>(.*)</body>.*$#ms', '#<h2>PHP License</h2>.*$#ms',
			'#<h1>Configuration</h1>#',  "#\r?\n#", "#</(h1|h2|h3|tr)>#", '# +<#',
			"#[ \t]+#", '#&nbsp;#', '#  +#', '# class=".*?"#', '%&#039;%',
		'#<tr>(?:.*?)" src="(?:.*?)=(.*?)" alt="PHP Logo" /></a>'
			.'<h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#',
				'#<h1><a href="(?:.*?)\?=(.*?)">PHP Credits</a></h1>#',
				'#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#',
				"# +#", '#<tr>#', '#</tr>#'),
			array('$1', '', '', '', '</$1>' . "\n", '<', ' ', ' ', ' ', '', ' ',
			'<h2>PHP Configuration</h2>'."\n".'<tr><td>PHP Version</td><td>$2</td></tr>'.
			"\n".'<tr><td>PHP Egg</td><td>$1</td></tr>',
			'<tr><td>PHP Credits Egg</td><td>$1</td></tr>',
			'<tr><td>Zend Engine</td><td>$2</td></tr>' . "\n" .
			'<tr><td>Zend Egg</td><td>$1</td></tr>', ' ', '%S%', '%E%'),
			ob_get_clean());

		$sections = explode('<h2>', strip_tags($pi, '<h2><th><td>'));
		unset($sections[0]);

		$pi = array();
		foreach($sections as $section){
			$n = substr($section, 0, strpos($section, '</h2>'));
			preg_match_all(
				'#%S%(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?%E%#',
				$section, $askapache, PREG_SET_ORDER);
			foreach($askapache as $m) {
				if(isset($m[2])) {
					$pi[$n][$m[1]]=(!isset($m[3])||$m[2]==$m[3])?$m[2]:array_slice($m,2);
				}
			}
		}

		return ($return === false) ? print_r($pi) : $pi;
	}
}
