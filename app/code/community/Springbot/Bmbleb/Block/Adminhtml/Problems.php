<?php

class Springbot_Bmbleb_Block_Adminhtml_Problems extends Mage_Adminhtml_Block_Template
{
	public function __construct()
	{
		parent::__construct();
		$this->setTemplate("bmbleb/problems/index.phtml");
	}

	/**
	 * Uses PluginStatus helper to determine if major problem needs to be displayed globally
	 */
	public function getSolutions()
	{
		return Mage::helper('bmbleb/PluginStatus')->getPluginProblems();
	}
}
