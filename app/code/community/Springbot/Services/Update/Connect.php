<?php

class Springbot_Services_Update_Connect extends Springbot_Services_Update_Abstract
{
	protected $_allowedStability;

	public function run()
	{
		try {
			$this->_releases = $this->getConnectReleases();
			$toInstall = $this->getReleaseToInstall();
		} catch(Exception $e) {
			Springbot_Log::error($e);
			exit;
		}
		return $toInstall->v;
	}

	public function getConnectReleases()
	{
		$response = $this->get(self::RELEASES_XML);
		$code = $response->getStatus();

		if($code == 200) {
			$xml = $response->getBody();
			$parsed = $this->parse($xml);
			$this->_releases = $parsed->r;
		}

		if(!$this->_releases) {
			throw new Exception ("Server returned with status of {$code} when fetching releases. Please check connection.");
		}
		return $this->_releases;
	}

	public function getReleaseToInstall()
	{
		$version = $this->getVersion();
		return $this->getRelease($version);
	}

	public function getRelease($version = null)
	{
		$releases = $this->getSortedReleases();

		if(is_null($version)) {
			return $releases[0];
		} else {
			foreach($releases as $release) {
				if($release->v == $version) {
					return $release;
				}
			}
		}
		throw new Exception('Release number not found!');
	}

	public function getLatest()
	{
		$releases = $this->getSortedReleases();
		return $releases[0];
	}

	public function getLatestVersion()
	{
		if(!isset($this->_latest)) {
			$this->_latest = $this->getLatest();
		}
		return $this->_latest->v;
	}

	public function getReleases()
	{
		if(!isset($this->_releases)) {
			$this->_releases = $this->getConnectReleases();
		}
		return $this->_releases;
	}

	public function getSortedReleases()
	{
		$releases = array();
		foreach($this->getReleases() as $release) {
			if($this->allowStability($release)) {
				$releases[] = $release;
			}
		}

		if(count($releases) < 1) {
			throw new Exception ('No releases found!');
		}

		usort($releases, array($this, '_sortReleasesCallback'));
		return array_reverse($releases);
	}

	public function allowStability($release)
	{
		if($this->_getAllowedStability() == $release->s) {
			return true;
		} else if ($release->s == 'stable') {
			return true;
		}
		return false;
	}

	protected function _getAllowedStability()
	{
		if(!$this->_allowedStability) {
			$this->_allowedStability = Mage::getStoreConfig('springbot/config/stability');
		}
		return $this->_allowedStability;
	}

	protected function _sortReleasesCallback($a, $b)
	{
		return version_compare($a->v,$b->v);
	}
}
