<?php
class Springbot_Bmbleb_Adminhtml_LogsController extends Mage_Adminhtml_Controller_Action
{

	public function indexAction()
	{
		$this->loadLayout();
		$this->renderLayout();
	}

	public function downloadAction() {
		$logName = $this->getRequest()->getParam('name');;
		$logPath = Mage::getBaseDir('log') . DS . $logName;
		if (!is_file($logPath) || !is_readable($logPath)) {
			throw new Exception();
		}
		$this->getResponse()
			->setHttpResponseCode(200)
			->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true )
			->setHeader('Pragma', 'public', true )
			->setHeader('Content-type', 'application/force-download')
			->setHeader('Content-Length', filesize($logPath))
			->setHeader('Content-Disposition', 'attachment' . '; filename=' . basename($logPath) );
		$this->getResponse()->clearBody();
		$this->getResponse()->sendHeaders();
		readfile($logPath);
		exit;
	}

}
