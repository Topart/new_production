<?php

class Springbot_Services_Update_Installer extends Springbot_Services_Update_Abstract
{
	protected $_package;

	const DIRMODE = 0755;
	const FILEMODE = 0644;

	public function __construct(Springbot_Services_Update_Package $package = null)
	{
		if(is_null($package)) {
			throw new Exception('Package object required!');
		}
		$this->_package = $package;
	}

	public function run()
	{
		try {
			foreach($this->getPackageContents() as $file) {
				Springbot_Log::info("Put $file");
				$this->putFile($file);
			}
		} catch (Exception $e) {
			Springbot_Log::error($e);
		}
		$this->_package->cleanUp();
	}

	public function getPackageContents()
	{
		return $this->_package->getContents();
	}

	public function getInstallPath($file)
	{
		$realPath = realpath($file);
		return empty($realPath) ? Mage::getBaseDir() . DS . $file : $realPath;
	}

	public function putFile($file)
	{
		$source = $this->_package->getTempFilePath($file);
		$dir = dirname($this->getInstallPath($file));
		$dest = $dir . DS . basename($file);
		@mkdir($dir, self::DIRMODE, true);
		if(is_file($source)) {
			Springbot_Log::info("Copy $source to $dest");
			@copy($source, $dest);
			@chmod($dest, self::FILEMODE);
		} else {
			Springbot_Log::info("Creating directory $source");
			@mkdir($source);
		}
	}
}
