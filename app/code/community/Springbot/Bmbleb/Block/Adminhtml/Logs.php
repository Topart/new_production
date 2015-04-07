<?php

class Springbot_Bmbleb_Block_Adminhtml_Logs extends Mage_Adminhtml_Block_Template
{
	public function __construct()
	{
		$this->setTemplate("bmbleb/logs/index.phtml");
		$this->_blockGroup = 'bmbleb';
		parent::__construct();
	}

	public function getLogContent($logName)
	{
		return htmlspecialchars(Mage::helper('combine')->getLogContents($logName));
	}
}
