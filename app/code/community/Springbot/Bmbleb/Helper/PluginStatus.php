<?php

class Springbot_Bmbleb_Helper_PluginStatus extends Mage_Core_Helper_Abstract
{
	const REPORT_PROBLEMS_INTERVAL_SECONDS = 604800; // Seven days in seconds
	const TOO_MANY_HOURS                   = 3; // Minimum number of hours since harvest to display warning


	/**
	 * Get a list of all potential plugin problems to display on the problems page
	 *
	 * Returns a detailed list of all issues (used on the problems page) so that the user may give the support team
	 * more information for troubleshooting what the actual issue may be.
	 */
	public function getPluginProblems()
	{
		$problems = array();


		if ($this->_emailPasswordSet() && !$this->_harvestInFlight()) {
			if (($missingGuids = $this->_getMissingStoreGuids())) {
				$problems[] = array(
					'problem' => 'Missing GUIDs for the following stores: ' . $missingGuids,
					'solution' => 'This problem can usually be fixed by re-logging into your Springbot account. '
				);
			}
			if ($this->_tokenIsInvalid()) {
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

		// Report any plugin problems to the Springbot API once a week or for the very first time
		if ($problems) {
			$lastApiReportHash = Mage::getStoreConfig('springbot/config/reported_problems_hash', Mage::app()->getStore());
			$currentProblemsHash = md5(serialize($problems));

			if ($lastApiReportHash != $currentProblemsHash) {
				Mage::getModel('core/config')->saveConfig('springbot/config/reported_problems_hash', $currentProblemsHash, 'default', 0);
				$this->_postProblemsToApi($problems);
			}
		}


		return $problems;
	}


	public function needsToLogin() {
		if ($this->_emailPasswordSet()) return false;
		else return true;
	}

	/**
	 * Check to make sure user has logged in to avoid showing a problem notification before they even login
	 */
	private function _emailPasswordSet()
	{
		if (
			Mage::getStoreConfig('springbot/config/account_email') &&
			Mage::getStoreConfig('springbot/config/account_password')
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
	private function _tokenIsInvalid()
	{
		if (Mage::getStoreConfig('springbot/config/security_token')) {
			return false;
		}
		else {
			return true;
		}
	}

	/**
	 * Check if a GUID exists for every store
	 */
	private function _getMissingStoreGuids()
	{
		$missingGuids = array();
		foreach (Mage::app()->getStores() as $store) {
			$storeId = Mage::getStoreConfig('springbot/config/store_id_' . $store->getId());
			$storeGuid = Mage::getStoreConfig('springbot/config/store_guid_' . $store->getId());
			if ($storeId && !$storeGuid) {
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
	 * Check to see if Magento tmp directory is writable
	 */
	private function _tmpDirIsWritable()
	{
		return is_writable(Mage::getBaseDir('tmp'));
	}

	/**
	 * Check to see if Magento log directory is writable
	 */
	private function _logDirIsWritable()
	{
		return is_writable(Mage::getBaseDir('log'));
	}

	/**
	 * Check to see if Magento tmp directory is writable
	 */
	private function _tmpDirIsReadable()
	{
		return is_readable(Mage::getBaseDir('tmp'));
	}

	/**
	 * Check to see if Magento log directory is writable
	 */
	private function _logDirIsReadable()
	{
		return is_readable(Mage::getBaseDir('log'));
	}

	/**
	 * Take array of problems and post it to the Springbot API
	 */
	private function _postProblemsToApi($problems)
	{
		try {
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
			Mage::getModel('combine/api')->call('installs', $dataJson, false);
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
}
