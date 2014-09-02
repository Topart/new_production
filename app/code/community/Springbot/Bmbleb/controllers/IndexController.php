<?php
class Springbot_Bmbleb_IndexController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
	{
		$this->loadLayout();
		$this->renderLayout();
	}

	public function termsAction()
	{
		print $this->getLayout()->createBlock("bmbleb/Adminhtml_Index_Terms")->toHtml();
		exit();
	}
}

