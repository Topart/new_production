<?php

class Springbot_Services_Update_Downloader extends Springbot_Services_Update_Abstract
{
	protected $_version;

	public function __construct($version)
	{
		$this->_version = $version;
	}

	public function run()
	{
		$checksum = $this->getRemoteContents('checksum');
		$archive = $this->getRemoteContents('archive');

		if($checksum !== md5($archive)) {
			throw new Exception('Remote archive does not match checksum!');
		}

		return $this->putFile($archive);
	}

	public function putFile($archive)
	{
		$file = Mage::getModel('combine/file_io');
		$file->write($this->_getFilename(), $archive);
		return $file->getFilename();
	}

	public function getRemoteContents($type)
	{
		$response = $this->get($this->{'_get' . ucfirst($type) . 'Uri'}());

		if($response->getStatus() == 200) {
			$body = $response->getBody();
		} else {
			throw new Exception("Could not get {$type}!");
		}
		return $body;
	}

	public function setVersion($version)
	{
		$this->_version = $version;
		return $this;
	}

	protected function _getFilename()
	{
		return "{$this->_package}-{$this->_version}.tgz";
	}

	protected function _getArchiveUri()
	{
		return "{$this->_version}/{$this->_package}-{$this->_version}.tgz";
	}

	protected function _getChecksumUri()
	{
		return "{$this->_version}/checksum";
	}
}
