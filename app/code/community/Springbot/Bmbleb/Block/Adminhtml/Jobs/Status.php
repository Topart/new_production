<?php

class Springbot_Bmbleb_Block_Adminhtml_Jobs_Status extends Mage_Adminhtml_Block_Template
{
	public function __construct()
	{
		parent::__construct();
		$this->setTemplate("bmbleb/jobs/status.phtml");
	}

	public function getManager()
	{
		return Mage::getModel('combine/cron_manager_status');
	}

	public function getAjaxToggleUrl()
	{
		return Mage::helper('adminhtml')->getUrl('*/*/toggleWorkerStatus');
	}

	public function renderAsJson()
	{
		return json_encode(array(
			'button_text' => $this->getButtonLabelText(),
			'label_text' => $this->getLabelText(),
		));
	}

	public function getLabelText()
	{
		if($this->getManager()->isActive()) {
			return $this->_getEnabledMessage();
		}
		else if($this->getManager()->isBlocked()) {
			return $this->_getBlockedMessage();
		}
		else {
			return $this->_getDisabledMessage();
		}
	}

	protected function _getEnabledMessage()
	{
		$mgr = $this->getManager();
		return 'Manager is <span class="status-alert-' .  $mgr->getStatus() . '">' .
			$mgr->getStatus() . '</span>, and has been running for ' . $mgr->getRuntime() . ' seconds';
	}

	protected function _getBlockedMessage()
	{
		return 'The Manager is <span class="status-alert-' .
		Springbot_Combine_Model_Cron_Manager_Status::INACTIVE . '">DISABLED</span>! ' .
		'There may be available jobs left in the queue but will not be processed until the work manger is restarted. ' .
		'This is likely because a stop work command was issued. ';
	}

	protected function _getDisabledMessage()
	{
		return 'The Manager is not running. ' .
			'This is most likely because there are no available jobs left to run. ' .
			'We will continue to queue jobs for syncing as they happen.';
	}

	public function getButtonLabelText()
	{
		$manager = $this->getManager();

		if($manager->isActive()) {
			$text = 'Deactivate Manager';
		} else {
			$text = 'Start Manager';
		}
		return $this->__($text);
	}
}
