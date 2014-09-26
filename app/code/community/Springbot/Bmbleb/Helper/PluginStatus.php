<?php

class Springbot_Bmbleb_Helper_PluginStatus extends Mage_Core_Helper_Abstract
{
	const REPORTED_PROBLEMS_HASH_CONFIG    = 'springbot/config/reported_problems_hash';
	const REPORT_PROBLEMS_INTERVAL_SECONDS = 604800; // Seven days in seconds
	const TOO_MANY_HOURS                   = 3; // Minimum number of hours since harvest to display warning
	const STORE_TIMESTAMP_GLOB             = 'sb-cache-healthcheck-*.dat'; // Globbing string to find all
	const STORE_GUID_CONFIG_PREFIX         = 'store_guid_'; // Prefix for the store guid config name
	const STORE_ID_CONFIG_PREFIX           = 'store_id_'; // Prefix for the store id config name

	/**
	 * Run all checks for fatal problems
	 *
	 * Returns any issues that would prevent a harvest that can be checked on each plugin page load, once a fatal
	 * plugin problem is detected the user is redirected to the problems page where they are presented with a more
	 * detailed list of problems and instructed to either login again or contact support.
	 */
	public function getFatalPluginProblems()
	{
		return $this->_getPluginProblems(true, false, false);
	}

	/**
	 * Returns a list of potential problems that should be brought to the user's attention for the magento global
	 * notifications.
	 */
	public function getGlobalPluginProblems()
	{
		return $this->_getPluginProblems(true, true, false);
	}

	/**
	 * Returns all problems (used for the problems page) so that they can see a detailed list of problems
	 * and possible fixes.
	 */
	public function getAllPluginProblems()
	{
		return $this->_getPluginProblems(true, true, true);
	}

	/**
	 * Get a list of all potential plugin problems to display on the problems page
	 *
	 * Returns a detailed list of all issues (used on the problems page) so that the user may give the support team
	 * more information for troubleshooting what the actual issue may be.
	 */
	private function _getPluginProblems($fatalProblems, $globalProblems, $possibleProblems)
	{
		$configVars = Mage::getStoreConfig('springbot/config');
		$problems = array();


		// Fatal problems are problems that will cause the Springbot section to redirect to the problems page
		if ($fatalProblems) {

			if ($this->_emailPasswordSet($configVars) && !$this->_harvestInFlight()) {
				if (($missingGuids = $this->_getMissingStoreGuids($configVars))) {
					$problems[] = array(
						'problem' => 'Missing GUIDs for the following stores: ' . $missingGuids,
						'solution' => 'This problem can usually be fixed by re-logging into your Springbot account. '
					);
				}
				if ($this->_tokenIsInvalid($configVars)) {
					$problems[] = array(
						'problem' => 'Security token is invalid',
						'solution' => 'This problem can usually be fixed by re-logging into your Springbot account. '
					);
				}
			}

			if (!$this->_logDirIsWritable()) {
				$problems[] = array(
					'problem' => 'Magento log directory is not writable',
					'solution' => 'This server configuration problem often occurs when the owner of the directory "var/log" in your '
						. 'Magento root folder is different than the user your webserver runs as. To fix this issue '
						. 'navigate to your Magento directory and run the command "chown &lt;your webserver user&gt; var/log". '
				);
			}
			if (!$this->_tmpDirIsWritable()) {
				$problems[] = array(
					'problem' => 'Magento tmp directory is not writable',
					'solution' => 'This server configuration problem often occurs when the owner of the directory "var/tmp" in your '
						. 'Magento root folder is different than the user your webserver runs as. To fix this issue '
						. 'navigate to your Magento directory and run the command "chown &lt;your webserver user&gt; var/tmp". '
				);
			}
			if (!$this->_logDirIsReadable()) {
				$problems[] = array(
					'problem' => 'Magento log directory is not readable',
					'solution' => 'This server configuration problem often occurs when the owner of the directory "var/log" in your '
						. 'Magento root folder is different than the user your webserver runs as. To fix this issue '
						. 'navigate to your Magento directory and run the command "chown &lt;your webserver user&gt; var/log". '
				);
			}
			if (!$this->_tmpDirIsReadable()) {
				$problems[] = array(
					'problem' => 'Magento tmp directory is not readable',
					'solution' => 'This server configuration problem often occurs when the owner of the directory "var/tmp" in your '
						. 'Magento root folder is different than the user your webserver runs as. To fix this issue '
						. 'navigate to your Magento directory and run the command "chown &lt;your webserver user&gt; var/tmp". '
				);
			}
			if ($secondsSinceHarvest = $this->_tooLongSinceCheckin()) {
				$hoursSinceHarvest = round($secondsSinceHarvest / 60 / 60);
				$problems[] = array(
					'problem' => 'It\'s been ' . $hoursSinceHarvest . ' hours since the Springbot plugin last checked in',
					'solution' => 'This problem occurs when the Springbot plugin has gone too long since it last '
						. 'communicated with the Springbot server. This issue can often be resolved by simply '
						. 're-logging in to your Springbot dashboard. '
				);
			}
			if (!$this->_correctPhpPath()) {
				$problems[] = array(
					'problem' => 'Incorrect PHP executable path',
					'solution' => 'This usually means that the default PHP path for your server cannot run in CLI mode. '
						. 'To fix this issue locate the full path of the PHP executable that you would use to run cron '
						. 'jobs and paste it into the "PHP Executable" setting in your Springbot configuration. '
				);
			}

		}

		// Global problems are problems that will cause a top notification on the Magento dashboard only
		if ($globalProblems) {

		}

		// Report any plugin problems to the Springbot API once a week or for the very first time
		$configModel = Mage::getModel('core/config');
		if ($problems) {
			$lastApiReportHash = Mage::getStoreConfig(self::REPORTED_PROBLEMS_HASH_CONFIG, Mage::app()->getStore());
			$currentProblemsHash = md5(serialize($problems));

			if ($lastApiReportHash != $currentProblemsHash) {
				$configModel->saveConfig(self::REPORTED_PROBLEMS_HASH_CONFIG, $currentProblemsHash, 'default', 0);
				$this->_postProblemsToApi($problems);
			}
		}

		// Possible problems include issues which might not necessarily cause a problem, but may offer insight into other problems
		if ($possibleProblems) {
			if (!$this->_mediaDirIsWritable()) {
				$problems[] = array(
					'problem' => 'The media directory is not writable',
					'solution' => 'The Magento media directory is not writable. While this should not affect the Springbot plugin '
						. 'it may be evidence of further permission and configuration issues. Ideally the owner of the media '
						. 'directory should be the same user that your webserver software runs as.'
				);
			}
		}

		return $problems;
	}


	public function needsToLogin() {
		$configVars = Mage::getStoreConfig('springbot/config');
		if ($this->_emailPasswordSet($configVars)) return false;
		else return true;
	}

	/**
	 * Check to make sure user has logged in to avoid showing a problem notification before they even login
	 */
	private function _emailPasswordSet($configVars)
	{
		if (
			isset($configVars['account_email']) &&
			isset($configVars['account_password']) &&
			$configVars['account_email'] &&
			$configVars['account_password']
		) {
			return true;
		}
		else {
			return false;
		}
	}

	private function _harvestInFlight()
	{
		return Mage::helper('combine/harvest')->isHarvestRunning();
	}

	/**
	 * Check if token is valid. Ideally we would want to check the actual validity of the token but we avoid that since
	 * it would involve phoning home on each admin page load.
	 */
	private function _tokenIsInvalid($configVars)
	{
		if ($configVars['security_token']) {
			return false;
		}
		else {
			return true;
		}
	}

	/**
	 * Check if a GUID exists for every store
	 */
	private function _getMissingStoreGuids($configVars)
	{
		$missingGuids = array();
		foreach (Mage::app()->getStores() as $store) {
			if (isset($configVars[self::STORE_ID_CONFIG_PREFIX . $store->getId()])) {
				$storeId = $configVars[self::STORE_ID_CONFIG_PREFIX . $store->getId()];
			}
			if (isset($configVars[self::STORE_GUID_CONFIG_PREFIX . $store->getId()])) {
				$storeGuid = $configVars[self::STORE_GUID_CONFIG_PREFIX . $store->getId()];
			}
			if (isset($storeId) && isset($storeGuid) && $storeId && !$storeGuid) {
				$missingGuids[] = $store->getId();
			}
		}
		if ($missingGuids) {
			return implode(', ', $missingGuids);
		}
		else {
			return false;
		}

	}

	/**
	 * Check to see if its been a long time since the last checkin
	 */
	private function _tooLongSinceCheckin()
	{
		$fileNameGlob = $this->_getFilenameGlob();
		$currentTime = time();
		$matches = glob($fileNameGlob);
		$secondsSinceLastCheckin = null;
		$mostRecentCheckin = null;
		foreach ($matches as $match) {
			$checkinTimestap = file_get_contents($match);
			$secondsSinceLastCheckin = $currentTime - $checkinTimestap;
			if (!$mostRecentCheckin || ($secondsSinceLastCheckin < $mostRecentCheckin)) {
				$mostRecentCheckin = $secondsSinceLastCheckin;
			}
		}

		if (($mostRecentCheckin === null) || ($mostRecentCheckin > (self::TOO_MANY_HOURS * 60 * 60))) {
			return $mostRecentCheckin;
		}
		else {
			return false;
		}
	}


	/**
	 * Check to see if Magento tmp directory is writable
	 */
	private function _tmpDirIsWritable()
	{
		return is_writable(Mage::getBaseDir() . '/var/tmp');
	}

	/**
	 * Check to see if Magento log directory is writable
	 */
	private function _logDirIsWritable()
	{
		return is_writable(Mage::getBaseDir() . '/var/log');
	}

	/**
	 * Check to see if Magento tmp directory is writable
	 */
	private function _tmpDirIsReadable()
	{
		return is_readable(Mage::getBaseDir() . '/var/tmp');
	}

	/**
	 * Check to see if Magento log directory is writable
	 */
	private function _logDirIsReadable()
	{
		return is_readable(Mage::getBaseDir() . '/var/log');
	}

	/**
	 * Check to see if Magento log directory is writable
	 */
	private function _mediaDirIsWritable()
	{
		return is_writable(Mage::getBaseDir('media'));
	}

	/**
	 * Take array of problems and post it to the Springbot API
	 */
	private function _postProblemsToApi($problems)
	{
		try{
			$baseStoreUrl = Mage::getStoreConfig('springbot/config/web/unsecure/base_url');
			$data = array(
				'store_url' => $baseStoreUrl,
				'problems' => array(),
				'springbot_store_ids' => $this->_getSpringbotStoreIds()
			);
			foreach ($problems as $problem) {
				$data['problems'][] = $problem['problem'];
			}
			$dataJson = json_encode($data);
			$apiModel = Mage::getModel('combine/api');
			$apiModel->call('installs', $dataJson, false);
		} catch (Exception $e) {
			// this call completing is not mission critical
			Springbot_Log::error($e);
		}
	}

	/**
	 * There may not be any store IDs yet but return them if there are.
	 */
	private function _getSpringbotStoreIds()
	{
		$springbotStoreIds = array();
		foreach (Mage::app()->getStores() as $store) {
			if ($springbotStoreId = Mage::getStoreConfig('springbot/config/store_id_' . $store->getId())) {
				$springbotStoreIds[] = $springbotStoreId;
			}
		}
		return $springbotStoreIds;
	}

	private function _getFilenameGlob()
	{
		return Mage::getBaseDir('tmp') . DS . self::STORE_TIMESTAMP_GLOB;
	}

	private function _correctPhpPath()
	{
		$phpPath = Mage::helper('combine/harvest')->getPhpExec();
		ob_start();
		Springbot_boss::spawn("{$phpPath} -r \"echo '<!-- PHP Test -->';\"", $output);
		$result = ob_get_clean();
		return trim($result) == '<!-- PHP Test -->';
	}

}
