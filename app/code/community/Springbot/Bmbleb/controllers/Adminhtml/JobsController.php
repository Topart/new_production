<?php
class Springbot_Bmbleb_Adminhtml_JobsController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
	{
		$this->loadLayout();
		$this->_setActiveMenu('springbot_bmbleb/dashboard');
		$this->renderLayout();
	}

	public function toggleWorkerStatusAction()
	{
		Springbot_Log::debug('Toggling work manager status');
		$manager = Mage::getModel('combine/cron_manager_status');
		$manager->toggle();

		sleep(1); // give everything a second to process

		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody(
			$this->getLayout()->createBlock('bmbleb/adminhtml_jobs_status')->renderAsJson()
		);
	}

	public function statusAction()
	{
		$this->loadLayout();
		$this->getResponse()->setBody(
			$this->getLayout()->createBlock('bmbleb/adminhtml_jobs_status')->toHtml()
		);
	}

	public function gridAction()
	{
		$this->loadLayout();
		$this->getResponse()->setBody(
			$this->getLayout()->createBlock('bmbleb/adminhtml_jobs_grid')->toHtml()
		);
	}

	public function runAction()
	{
		if($jobIds = $this->getRequest()->getParam('job_ids')) {
			foreach($jobIds as $jobId) {
				try {
					$job = $this->_loadJob($jobId);
					$job->run();
				} catch (Exception $e) {
					Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				}
			}
		}

		$this->_redirect('*/*/index');
	}

	public function deleteAction()
	{
		$jobIds = $this->getRequest()->getParam('job_ids');

		foreach($jobIds as $jobId) {
			try {
				$job = $this->_loadJob($jobId);
				$job->delete();
			} catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
			}
		}

		$this->_redirect('*/*/index');
	}

	protected function _loadJob($id)
	{
		return Mage::getModel('combine/cron_queue')->load($id);
	}
}
