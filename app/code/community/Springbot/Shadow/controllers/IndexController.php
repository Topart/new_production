<?php
class Springbot_Shadow_IndexController extends Mage_Core_Controller_Front_Action
{
	private $_acceptedParams = array('email');

	public function indexAction()
	{
		if ($quote = Mage::getSingleton('checkout/session')->getQuote()) {
			$params = $this->getRequest()->getParams();
			foreach ($params as $paramName => $paramValue) {
				if ($this->_isValidParam($paramName)) {
					if ($paramName == 'email') {
						$sessionQuoteExists = $quote->hasEntityId();
						if ($quote = Mage::getModel('checkout/session')->getQuote()) {
							// If there is no email address associated with the quote, check to see if one exists from our js listener
							if (!$quote->getCustomerEmail()) {
								$quote->setCustomerEmail($paramValue);
								$quote->save();
							}
						}
						if (!$sessionQuoteExists) {
							Mage::getSingleton('checkout/session')->setQuoteId($quote->getId());
						}
					}
				}
			}
		}

		$this->loadLayout()->getLayout()->getBlock('root')->setTemplate('shadow/emailupdate.phtml');
		$this->_initLayoutMessages('core/session');
		$this->renderLayout();
	}

	private function _isValidParam($paramName) {
		return in_array($paramName, $this->_acceptedParams);
	}

}
