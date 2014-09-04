<?php
class Springbot_Bmbleb_HelpController extends Mage_Adminhtml_Controller_Action
{
	
	protected function _initAction() {
        $this->_setActiveMenu('bmbleb/help/index');
		return $this;
	}   
	
	/*
	 * dashboard / login / register screen
	 */
    public function indexAction()
    {
        $this->loadLayout();

		$this->_addLeft(
			$this->getLayout()->createBlock('adminhtml/template')
			->setTemplate('bmbleb/tabs.phtml'));

        //Main template
        $this->_addContent(
        	$this->getLayout()->createBlock('adminhtml/template', 'main')
	        ->setTemplate('bmbleb/help.phtml'));

        $this->renderLayout();
    }


}