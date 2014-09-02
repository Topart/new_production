<?php
/**
 * Harvesting Manager
 *
 * @version		v1.0.45 - 12/26/2012
 *
 * @category    Magento Integrations
 * @package     springbot
 * @author 		William Seitz
 * @division	SpringBot Integration Team
 * @support		magentosupport@springbot.com
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */

class Springbot_DataServices_HarvestingManager
{
	const MAGENTO_PACKAGE_VERSION = '1.2.1.2';
	const ZERO                    = 0;
	const SUCCESSFUL_RESPONSE     = 'ok';
	const DATE_FORMAT             = 'Y-m-d H:i:s';

	private $rootAppPath          = '';
	private $eventHistoryFilename = '';

	public function __construct()
	{
		$this->rootAppPath = Mage::getBaseDir();
		$this->eventHistoryFilename = $this->rootAppPath . '/var/log/Springbot-EventHistory.csv';
	}

	public function harvestHealthCheck($storeId)
	{
		$this->springbotStoreID = Mage::helper('combine/harvest')->getSpringbotStoreId($storeId);
		$result = Mage::getModel('combine/api')->call('harvest_master', '{"store_id":"'.$this->springbotStoreID.'","version":"'.self::MAGENTO_PACKAGE_VERSION.'"}');

		echo "Harvest HealthCheck store: {$storeId}/{$this->springbotStoreID}" . PHP_EOL;

		if ($result['status']==self::SUCCESSFUL_RESPONSE) {

			foreach ($result['commands'] as $cmd) {
				$this->showMessage('Command->'.$cmd['command'].' is being processed.');
				switch (trim(strtolower($cmd['command']))) {
				case 'get_log':
					if (isset($cmd['data']['log_name'])) {
						$this->getlog($cmd['data']['log_name']);
					} else {
						$this->getlog();
					}
					break;

				case 'setvar':
				case 'set_var':
					$this->setConfigVar($cmd['data']['var_name'], $cmd['data']['var_value']);
					Mage::getConfig()->cleanCache();
					break;

				case 'post':
					$type = isset($cmd['data']['type']) ? $cmd['data']['type'] : null;
					$id = isset($cmd['data']['entity_id']) ? $cmd['data']['entity_id'] : null;
					$this->postItem($type, $id);

				case 'package_update':
					$version = isset($cmd['data']['package_version']) ? $cmd['data']['package_version'] : null;
					$this->updatePackage($version);
					break;

				case 'full_harvest':
				case 'launch_full_harvest':
					Springbot_Boss::launchHarvest();
					break;

				case 'partial_harvest':
					$type = isset($cmd['data']['type']) ? $cmd['data']['type'] : null;
					$this->launchPartialHarvest($storeId, $type);
					break;

				case 'resume_harvest':
					Mage::getConfig()->cleanCache();
					Mage::getConfig()->reinit();
					$this->resumeHarvest();
					break;

				case 'file_replace':
					$this->fileReplace($cmd['data']['target_dir'],$cmd['data']['source']);
					break;

				case 'adroll_turn_on':
					$this->setAdrollFeature($cmd['store_id'],$cmd,true);
					break;

				case 'adroll_turn_off':
					$this->setAdrollFeature($cmd['store_id'],$cmd,false);
					break;

				case 'kill_harvest':
					Springbot_Boss::halt();
					break;

				case 'table_data':
					$this->logTableData();
					break;

				case 'forecast':
					Springbot_Services_Cmd_Forecast::forecastAllStores();
					break;

				case 'skip_store_harvest':
					Springbot_Boss::haltStore($cmd['store_id']);
					break;

				}
			}
			$this->deliverEventLog();
		}
		return;
	}

	public function getlog($logName = '')
	{
		$buffer = Mage::helper('combine')->getLogContents($logName);

		$logData = array(
			'logs' => array(
				array(
					'store_id' => $this->springbotStoreID,
					'description' => $buffer,
				),
			),
		);

		$method = 'logs';

		Mage::getModel('combine/api')->call($method, json_encode($logData), false);

		if (isset($result['status'])) {
			if ($result['status']==self::SUCCESSFUL_RESPONSE) {
				$error_msg='was successfully delivered';
			} else {
				$error_msg='delivery failed ->'.$result['status'];
			}
		}
		$this->showMessage('['.__METHOD__.'] '.' '.$error_msg);
	}

	private function setConfigVar($varName,$varValue)
	{
		if(!preg_match('/.*\/.*\/.*/', $varName)) {
			$varName = 'springbot/config/' . $varName;
		}
		if (!empty($varName)) {
			$this->set_config($varName, $varValue);
		}
		return;
	}

	private function deliverEventLog()
	{
		$maxRecord                   = 8192;
		$delimiter                   = ',';
		$eventHistoryArchiveFilename = $this->rootAppPath.'/var/log/Springbot-EventHistory-Archive.csv';

		if (file_exists($eventHistoryArchiveFilename)) {
			$this->showMessage('Purge existing archive '.$eventHistoryArchiveFilename);
			unlink ($eventHistoryArchiveFilename);
		}
		$this->showMessage('Snapshot '.$this->eventHistoryFilename.' -> '.$eventHistoryArchiveFilename);
		copy($this->eventHistoryFilename, $eventHistoryArchiveFilename);
		$handle = fopen($this->eventHistoryFilename, 'w');
		if ($handle) {
			$this->showMessage('Erasing '.$this->eventHistoryFilename);
			fclose($handle);
		} else {
			$this->showMessage('Open/Erase failed on '.$this->eventHistoryFilename);
		}

		/* Get Unique Store Number */
		$store_number_list=array();
		if (($handle = fopen($eventHistoryArchiveFilename, 'r')) !== FALSE) {
			while (($rawRow = fgetcsv($handle, $maxRecord, $delimiter)) !== FALSE) {
				$data        = array();
				$storeNumber = '';
				switch ($this->captureValue(0,$rawRow)) {
				case 'view':
					$storeNumber = $this->captureValue(5,$rawRow);
					break;
				case 'purchase':
					$storeNumber = $this->captureValue(5,$rawRow);
					break;
				case 'atc':
					$storeNumber = $this->captureValue(4,$rawRow);
					break;
				}
				if (!empty($storeNumber) && !in_array($storeNumber, $store_number_list)) {$store_number_list[]=$storeNumber;}
			}
			fclose($handle);
		}
		foreach ($store_number_list as $storeNumber) {
			if ($storeNumber=='' || empty($storeNumber)) {
			} else {

				$springbotStoreID = Mage::helper('combine/harvest')->getSpringbotStoreId($storeNumber);

				$logData = array();
				if (($handle = fopen($eventHistoryArchiveFilename, 'r')) !== FALSE) {
					$this->showMessage('Formatting'.$eventHistoryArchiveFilename);
					$actionCount=0;
					while (($rawRow = fgetcsv($handle, $maxRecord, $delimiter)) !== FALSE) {
						$actionCount++;
						$row  = array();
						$data = array();
						foreach ($rawRow as $val) {
							$row[]=preg_replace('/[^(\x20-\x7F)]*/','', $val);
						}
						$data['action']   = $this->captureValue(0,$row);
						$data['datetime'] = $this->captureValue(1,$row);

						switch ($data['action']) {
						case 'view':
							$currentURL              = $this->captureValue(2,$row);
							$currentIP               = $this->captureValue(4,$row);
							$data['page_url']        = $currentURL;
							$data['sku']             = $this->captureValue(3,$row);
							$data['sku_fulfillment'] = $this->captureValue(3,$row);
							$data['visitor_ip']      = $currentIP;
							$data['category_id']     = $this->captureValue(6,$row);
							$eventStoreNumber        = $this->captureValue(5,$row);
							break;

						case 'purchase':
							$data['sku']             = $this->captureValue(2,$row);
							$data['sku_fulfillment'] = $this->captureValue(3,$row);
							$data['purchase_id']     = $this->captureValue(4,$row);
							$data['category_id']     = $this->captureValue(6,$row);
							$eventStoreNumber        = $this->captureValue(5,$row);
							break;

						case 'atc':
							$data['sku']             = $this->captureValue(2,$row);
							$data['sku_fulfillment'] = $this->captureValue(2,$row);
							$data['quote_id']        = $this->captureValue(3,$row);
							$eventStoreNumber        = $this->captureValue(4,$row);
							$data['category_id']     = $this->captureValue(5,$row);
							break;

						default:
							$data['sku']             = '';
							$eventStoreNumber        = '';

						}
						if ($eventStoreNumber==$storeNumber && !empty($data['sku'])) { array_push($logData, $data); }

					}
					$this->showMessage('Store->'.$storeNumber.' had '.$actionCount.' actions extracted from '.$eventHistoryArchiveFilename);
					fclose($handle);
				}
				if (sizeof($logData)==self::ZERO) {
					$this->showMessage('Empty '.$eventHistoryArchiveFilename);
				} else {
					$this->showMessage('Delivering '.$eventHistoryArchiveFilename);
					$method			= 'stores/'.$springbotStoreID.'/products/actions/create';
					$result = Mage::getModel('combine/api')->call($method, json_encode($logData));

					if (isset($result['status'])) {
						if ($result['status']==self::SUCCESSFUL_RESPONSE) {
							$error_msg='was successfully delivered';
						} else {
							$error_msg='delivery failed ->'.$result['status'];
						}
					} else {
						$error_msg=' delivery failed (No status array) ->'.$result;
					}
					$this->showMessage('['.$method.'] '.$eventHistoryArchiveFilename.' '.$error_msg);
				}
			} // end foreach
		}
		return;
	}

	private function captureValue($ix,$rowArray)
	{
		if (isset($rowArray[$ix])) {
			return $rowArray[$ix];
		} else {
			return '';
		}
	}

	public function updatePackage($version)
	{
		$updater = new Springbot_Services_Cmd_Update();
		$updater->setVersion($version);
		$updater->run();
		return true;
	}

	private function fileReplace($target,$source)
	{
		$openModeOutput = 'w';
		$origSize       = self::ZERO;
		$newSize        = self::ZERO;

		$magentoRootDir = $this->rootAppPath;
		if (substr($target,0,1) != '/') {
			$qualifiedFilename = $magentoRootDir.'/'.$target;
		} else {
			$qualifiedFilename = $magentoRootDir.$target;
		}

		/* If file exists make a backup copy */

		if (file_exists($qualifiedFilename)) {
			$origSize=filesize($qualifiedFilename);
			copy($qualifiedFilename, $qualifiedFilename.'.backup');
		}

		$fHandle = fopen($qualifiedFilename,$openModeOutput);
		fwrite($fHandle, $source);
		fclose($fHandle);
		$newSize=filesize($qualifiedFilename);

		$this->showMessage('File Updated->'.$qualifiedFilename.' Original Size:'.$origSize.'; New Size:'.$newSize);

		return;
	}

	public function postItem($type, $id)
	{
		Springbot_Boss::internalCallback(
			"post:$id",
			array('i' => $id)
		);
	}

	private function resumeHarvest()
	{
		Springbot_Boss::internalCallback('work:manager');
	}

	private function launchPartialHarvest($storeId, $type)
	{
		Mage::helper('combine/harvest')->truncateEngineLogs();
		Springbot_Boss::scheduleJob(
			'cmd:harvest',
			array(
				's' => $storeId,
				'c' => $type,
			),
			Springbot_Services_Priority::HARVEST,
			'default',
			$storeId
		);
	}

	private function setAdrollFeature($storeId, $data, $enable)
	{
		$configPath = 'design/footer/absolute_footer';
		$pixel_code = html_entity_decode($data['data']['pixel_script'], ENT_QUOTES);
		$new_footer = '';
		$existing=$this->RemoveAdRoll($this->get_config($configPath));

		if ($enable) {
			if (strlen($pixel_code)>self::ZERO) {
				$new_footer = $this->InsertAdrollScript($existing,$pixel_code);
			}
		} else {
			$new_footer = $existing;
		}

		// Only set adroll for current store!
		$scope_id = Mage::app()->getStore()->getStoreId();
		$this->set_config($configPath, $new_footer, 'stores', $storeId);
		$this->showMessage($configPath.' is now ['.$new_footer.']');

		return;
	}

	private function RemoveAdRoll($existing)
	{
		$footerLength    = strlen($existing);
		$bannerPrefix    = "<!-- Springbot: Begin Adroll Script ";
		$endScriptMarker = "<!-- Springbot: End Adroll Script -->";

		$beginPointer    = strpos($existing, $bannerPrefix);
		$endPointer      = strpos($existing, $endScriptMarker);

		if ($endPointer>self::ZERO) {
			return $this->EraseScript($existing,$beginPointer,$endPointer);
		} else {
			return $existing;
		}
		return;
	}

	private function EraseScript($scriptCode,$begLoc,$endLoc)
	{
		$strLEN    = strlen($scriptCode);
		$newScript = '';
		for ($c=self::ZERO;$c<$strLEN;$c++) {
			if ($c<$begLoc || $c>$endLoc) {
				$newScript=$newScript.substr($scriptCode,$c,self::ZERO);
			}
		}
		return $newScript;
	}

	private function InsertAdrollScript($currentFooterCode,$ad_pixelCode)
	{
		$now = date(self::DATE_FORMAT);

		$newFooter = $currentFooterCode
			."<!-- Springbot: Begin Adroll Script ".$now.' -->'."\n"
			.$ad_pixelCode
			."<!-- Springbot: End Adroll Script -->";

		return $newFooter;
	}

	private function get_config($path)
	{
		return Mage::getStoreConfig($path);
	}

	private function set_config($path, $value, $scope = 'default', $scope_id = 0)
	{
		return Mage::getModel('core/config')->saveConfig($path, $value, $scope, $scope_id);
	}

	private function showMessage($msg,$abort=false,$ignoreMessage=true)
	{
		Springbot_Log::debug($msg);
		if ($ignoreMessage==false) {
			Springbot_Log::harvest($msg);
		}
		if ($abort) {
			Springbot_Log::harvest($msg);
			Springbot_Log::harvest('Process Abort requested');
			exit;
		}
		return;
	}

	public function logTableData() {
		$count = Mage::getModel('combine/cron_queue')->getCollection()->count();
		Springbot_Log::remote('Cron queue table size: ' . $count);
	}

}
