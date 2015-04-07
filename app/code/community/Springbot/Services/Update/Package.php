<?php

class Springbot_Services_Update_Package extends Springbot_Services_Update_Abstract
{
	const PACKAGE_XML = 'package.xml';

	protected $_archivePath;
	protected $_unpackedPath;
	protected $_contents;
	protected $_targetMap = array(
		"magelocal"     => "./app/code/local",
		"magecommunity" => "./app/code/community",
		"magecore"      => "./app/code/core",
		"magedesign"    => "./app/design",
		"mageetc"       => "./app/etc",
		"magelib"       => "./lib",
		"magelocale"    => "./app/locale",
		"magemedia"     => "./media",
		"mageskin"      => "./skin",
		"mageweb"       => ".",
		"magetest"      => "./tests",
		"mage"          => ".",
	);

	public function __construct($path = null)
	{
		if(!$path) {
			throw new Exception('Path requried!');
		}
		$this->_archivePath = $path;
	}

	public function unpack()
	{
		try {
			$this->_unpackedPath = $this->_readyPath();
			$this->_cmdTar($this->getArchivePath(), $this->_unpackedPath);
		} catch (Exception $e) {
			Springbot_Log::error($e);
		}
	}

	public function cleanUp()
	{
		@unlink($this->_archivePath);
		$this->_rmDir($this->_unpackedPath);
	}

	public function getContents()
	{
		if(!isset($this->_contents)) {
			$this->_prepareContents();
		}
		return $this->_contents;
	}

	public function getTempFilePath($file)
	{
		return realpath($this->getTempOutPath() . DS . $file);
	}

	public function getTempOutPath()
	{
		return Mage::getBaseDir('tmp') . DS . $this->getVersionNumber();
	}

	public function getVersionNumber()
	{
		return basename($this->_archivePath, '.' . self::EXT);
	}

	public function getArchivePath()
	{
		return $this->_archivePath;
	}

	public function getUnpackedPath()
	{
		return $this->_unpackedPath;
	}

	protected function _rmDir($dirPath)
	{
		foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
			$path->isFile() ? unlink($path->getPathname()) : rmdir($path->getPathname());
		}
		rmdir($dirPath);
	}

	protected function _prepareContents()
	{
		$xml = $this->_getPackageXml();
		if(!isset($xml->contents->target)) {
			return $this->_contents;
		}
		foreach($xml->contents->target as $target) {
			$targetUri = $this->_getTargetPath($target['name']);
			$this->_getList($target, $targetUri);
		}
		return $this->_contents;
	}

	protected function _getTargetPath($name)
	{
		$name = (string) $name;
		return isset($this->_targetMap[$name]) ? $this->_targetMap[$name] : '';
	}

	protected function _getList($parent, $path)
	{
		if (count($parent) == 0) {
			$this->_contents[] = $path;
		} else {
			foreach($parent as $_content) {
				$this->_getList($_content, ($path ? $path . DS : '')  . $_content['name']);
			}
		}
	}

	protected function _readyPath()
	{
		$path = $this->getTempOutPath();
		@mkdir($path, 0777, true);
		if(!is_writable($path)) {
			throw new Exception('Created extraction directory not writable!');
		}
		return $path;
	}

	protected function _cmdTar($file, $out)
	{
		Springbot_Cli::spawn("tar -zxf $file -C $out");
		if($this->_empty($out)) {
			throw new Exception('Tar empty!');
		}
		return $out;
	}

	protected function _empty($dir)
	{
		return !count(glob("$dir/*"));
	}

	protected function _getPackageXml()
	{
		return simplexml_load_file($this->getUnpackedPath() . DS . self::PACKAGE_XML);
	}
}
