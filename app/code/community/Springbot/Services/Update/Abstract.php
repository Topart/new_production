<?php

class Springbot_Services_Update_Abstract
{
	protected $_version;

	const CHANNELS_XML = "channels.xml";
	const CHANNEL_XML  = "channel.xml";
	const PACKAGES_XML = "packages.xml";
	const RELEASES_XML = "releases.xml";
	const PACKAGE_XML  = "package.xml";
	const EXT          = "tgz";

	protected $_channelUrl = 'http://connect20.magentocommerce.com/community';
	protected $_package = 'Springbot';

	public function get($uri = '')
	{
		$url = "{$this->_channelUrl}/{$this->_package}/{$uri}";
		return $this->_getClient($url)->request();
	}

	protected function _getClient($url)
	{
		$this->_client = new Zend_Http_Client($url);
		return $this->_client;
	}

	public function parse($xml)
	{
		try {
			$xml = simplexml_load_string($xml);
		} catch (Exception $e) {
			throw new Exception ('Releases not valid XML! Please check connection.');
		}
		return $xml;
	}

	public function getVersion()
	{
		return $this->_version;
	}

	public function setVersion($version)
	{
		$this->_version = $version;
		return $this;
	}
}
