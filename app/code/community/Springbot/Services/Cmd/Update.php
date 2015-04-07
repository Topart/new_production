<?php

class Springbot_Services_Cmd_Update extends Springbot_Services
{
	public function run()
	{
		if (Mage::getStoreConfig('springbot/config/remote_update') || $this->getForce()) {
			try {
				// Compilation includes configuration file
				define('MAGENTO_ROOT', getcwd());

				$compilerConfig = MAGENTO_ROOT . '/includes/config.php';
				if (file_exists($compilerConfig)) {
					include $compilerConfig;
				}

				// Exit if compilation enabled
				if (defined('COMPILER_INCLUDE_PATH')) {
					$msg = "Compilation appears to be enabled... exiting.";
					Springbot_Log::remote($msg);
					echo $msg . PHP_EOL;
					exit("Compilation appears to be enabled... exiting.");
				} else {
					Springbot_Log::info("Compilation Status: Disabled");
				}

				Springbot_Log::info("Updating Springbot extension");

				$connect = new Springbot_Services_Update_Connect;
				$connect->setVersion($this->_getVersion());
				$version = $connect->run();

				Springbot_Log::info("Update to version $version.");

				$downloader = new Springbot_Services_Update_Downloader($version);
				$archivePath = $downloader->run();

				Springbot_Log::info("Archive downloaded to $archivePath");

				$package = new Springbot_Services_Update_Package($archivePath);
				$package->unpack();

				Springbot_Log::info("Archive extracted to {$package->getUnpackedPath()}");

				$installer = new Springbot_Services_Update_Installer($package);
				$installer->run();

				Springbot_Log::info("Install was successful. Clearing cache.");

				Mage::app()->cleanCache();
			} catch (Exception $e) {
				Springbot_Log::error($e);
			}
			$msg = "Updated to version $version successfully!";
			Springbot_Log::remote($msg);
			echo $msg . PHP_EOL;
		} else {
			throw new Exception('Remote update not allowed by configuration! Please enable or use -f param.');
		}
	}

	protected function _getVersion()
	{
		return isset($this->_data['version']) ? $this->_data['version'] : null;
	}
}
