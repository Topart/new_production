<?php

class Springbot_Combine_Model_File_Io
{
	const TRUNCATE = 'w';
	const APPEND = 'a';
	const READ = 'r';

	protected $_filename;
	protected $_resource;
	protected $_mode;
	protected $_path;

	public function __destruct()
	{
		@fclose($this->_resource);
	}

	protected function _init($filename, $mode)
	{
		$this->setFilename($this->_getPath()->resolve($filename, 'tmp'));
		$this->_setMode($mode);

		if(empty($this->_filename)) {
			throw new Exception('Filename required for I/O.');
		}
		$this->_openFile();
	}

	public function write($filename, $content)
	{
		$this->_init($filename, self::TRUNCATE);

		if(fwrite($this->_getResource(), $content) === false) {
			throw new Exception('Writing to file ' . $this->_filename . ' failed.');
		}
	}

	public function read($filename)
	{
		$this->_init($filename, self::READ);

		if(!($content = fread($this->_getResource(), $this->_getStreamLength()))) {
			throw new Exception('Reading from file ' . $this->_filename . ' failed.');
		}
		return $content;
	}

	public function exists($filename)
	{
		$path = $this->_getPath()->resolve($filename, 'tmp');

		return @file_exists($path);
	}

	public function delete()
	{
		@unlink($this->_filename);
	}

	public function getBaseFilename()
	{
		return basename($this->_filename);
	}

	public function getFilename()
	{
		return $this->_filename;
	}

	public function setFilename($filename)
	{
		$this->_filename = $filename;
		return $this;
	}

	protected function _openFile()
	{
		if(empty($this->_filename)) {
			throw new Exception('Filename required to open file.');
		}

		if(!file_exists($this->_filename) && $this->_doCreate()) {
			$this->_createFile();
		}

		$this->_resource = $this->_getResource();

		if(!$this->_resource) {
			throw new Exception('Could not open file for reading.');
		}

		return $this;
	}

	protected function _createFile()
	{
		@file_put_contents($this->_filename, '');
		@chmod($this->_filename, 0777);
		if(!file_exists($this->_filename)) {
			throw new Exception('Invalid filename.');
		}
		return $this;
	}

	protected function _getResource()
	{
		if(!isset($this->_resource)) {
			$this->_resource = @fopen($this->_filename, $this->_getMode());
		}
		return $this->_resource;
	}

	protected function _getPath()
	{
		if(!isset($this->_path)) {
			$this->_path = Mage::getModel('combine/file_path');
		}
		return $this->_path;
	}

	protected function _setMode($mode)
	{
		$this->_mode = $mode;
		return $this;
	}

	protected function _getMode()
	{
		if(!isset($this->_mode)) {
			throw new Exception('No mode set for I/O.');
		}
		return $this->_mode;
	}

	protected function _getStreamLength()
	{
		$filesize = @filesize($this->_filename);
		return $filesize ? $filesize : 1024;
	}

	protected function _doCreate()
	{
		return $this->_getMode() != self::READ;
	}
}
