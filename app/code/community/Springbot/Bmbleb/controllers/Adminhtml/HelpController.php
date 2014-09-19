<?php
class Springbot_Bmbleb_Adminhtml_HelpController extends Mage_Adminhtml_Controller_Action
{

	public function indexAction()
	{
		$this->loadLayout();
		$this->_setActiveMenu('springbot_bmbleb/dashboard');
		$this->renderLayout();
	}

}
