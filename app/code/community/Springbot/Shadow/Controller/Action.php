<?php

class Springbot_Shadow_Controller_Action extends Mage_Core_Controller_Front_Action
{
	public function preDispatch()
	{
		$this->setFlag('', self::FLAG_NO_PRE_DISPATCH, 1);
		$this->setFlag('', self::FLAG_NO_POST_DISPATCH, 1);
		parent::preDispatch();
		return $this;
	}

	/**
	 * Return whether or not the current request is authenticated by a
	 * Springbot token in the header
	 *
	 * @return boolean
	 */
	public function hasSbAuthToken()
	{
		$helper = Mage::helper('shadow/prattler');
		$token = $helper->getPrattlerToken();
		if (!$token) {
			Springbot_Log::debug('Could not load security token to authenticated jobs endpoint');
		}
		else if ($this->getRequest()->getHeader('Springbot-Security-Token') != $token) {
			Springbot_Log::debug('Supplied security token hash does not match');
		} else {
			return true;
		}
		return false;
	}
}
