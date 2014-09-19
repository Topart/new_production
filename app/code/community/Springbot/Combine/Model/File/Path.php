<?php

class Springbot_Combine_Model_File_Path
{
	protected $_baseDir;
	protected $_filename;
	protected $_type;

	public function getBaseDir($type = 'base')
	{
		if(isset($this->_type)) {
			$type = $this->_type;
		}
		if(!isset($this->_baseDir)) {
			$this->_baseDir = Mage::getBaseDir($type);
		}
		return $this->_baseDir;
	}

	public function setBaseDir($path)
	{
		$this->_baseDir = $path;
		return $this;
	}

	public function setBaseDirType($type)
	{
		$this->_type = $type;
		return $this;
	}

	public function setFilename($name)
	{
		$this->_filename = $name;
		return $this;
	}

	public function getFilename()
	{
		return $this->_filename;
	}

	public function getAbsolutePath()
	{
		return $this->getBaseDir() . DS . $this->getFilename();
	}

	public function resolve($filename = null, $type = null)
	{
		$filename = is_null($filename) ? $this->_filename : $filename;
		$type = is_null($type) ? $this->_type : $type;

		if(!isset($filename)) {
			throw new Exception('Filename required as property or argument.');
		}
		if(!isset($type)) {
			throw new Exception('Directory type required.');
		}

		$this->setFilename($filename);

		return $this->getBaseDir($type) . DS . $this->getFilename();
	}

	public function isWriteable($path = null)
	{
		if(is_null($path)) {
			$path = $this->getBaseDir();
		}
		if(!file_exists($path)) {
			throw new Exception('File or directory does not exist.');
		}
		return is_writable($path);
	}
}
