<?php
class Springbot_Bmbleb_Adminhtml_ProblemsController extends Mage_Adminhtml_Controller_Action
{

	public function indexAction()
	{
		if ($problems = Mage::helper('bmbleb/PluginStatus')->getPluginProblems()) {
			Mage::getSingleton('core/session')->addError('If this problem persists please contact Springbot support.');
			$this->loadLayout();
			$this->_setActiveMenu('bmbleb/adminhtml_problems/index');
			$this->renderLayout();
		}
		else {
			Mage::getSingleton('core/session')->addSuccess('Springbot did not detect any errors.');
			$this->_redirect('bmbleb/adminhtml_index/status');
		}

	}

}
