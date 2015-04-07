<?php
class Springbot_Shadow_IndexController extends Springbot_Shadow_Controller_Action
{
	/**
	 * Order of priority desc - "there can only be one!"
	 */
	private $_redirectIds = array(
		'sb',
		'redirect_mongo_id',
	);

	public function indexAction()
	{
		try {
			$params = $this->getRequest()->getParams();

			// Maintain backward compatibility
			$aliases = array('run', 'healthcheck');
			foreach ($aliases as $alias) {
				if (isset($params[$alias])) {
					$params['task'] = $alias;
					unset($params[$alias]);
				}
			}

		 	if (isset($params['email'])) {
				$this->emailCaller();
			}
			else if (isset($params['trackable']) && isset($params['type'])) {
				$this->trackableCaller();
			}
			else if (isset($params['task'])) {
				if ($this->hasSbAuthToken()) {
					$task = $params['task'];
					unset($params['task']);
					if ($task = Springbot_Services_Tasks::makeTask($task, $params)) {
						$responseBody = json_encode($task->run());
						$this->getResponse()->setHeader('Content-type', 'application/json');
						$this->getResponse()->setBody($responseBody);
					}
				}
				else {
					$this->getResponse()->setHttpResponseCode(401);
				}
			}
			else if (isset($params['view_log'])) {
				if ($this->hasSbAuthToken()) {
					$this->viewLogCaller();
				}
				else {
					$this->getResponse()->setHttpResponseCode(401);
				}
			}

		} catch (Exception $e) {
			$helper = Mage::helper('shadow/prattler');
			$this->getResponse()->setHeader('Content-type', 'application/json');
			$this->getResponse()->setBody(json_encode($helper->getExceptionResponse($e)));
		}
	}


	private function emailCaller()
	{
		if ($quote = Mage::getSingleton('checkout/session')->getQuote()) {
			$sessionQuoteExists = $quote->hasEntityId();

			// If there is no email address associated with the quote, check to see if one exists from our js listener
			if (!$quote->getCustomerEmail()) {
				$quote->setCustomerEmail($this->getRequest()->getParam('email'));
				$quote->save();
			}

			if (!$sessionQuoteExists) {
				Mage::getSingleton('checkout/session')->setQuoteId($quote->getId());
			}

			$this->getResponse()->setHeader('Content-type', 'application/json');
			$this->getResponse()->setBody('{}');
		}
	}

	private function trackableCaller()
	{
		if (Mage::getSingleton('customer/session')->isLoggedIn()) {
			$customerData = Mage::getSingleton('customer/session')->getCustomer();
			$customerId = $customerData->getId();
		}
		else {
			$customerId = null;
		}

		if ($quote = Mage::getModel('checkout/session')->getQuote()) {
			$quoteId = $quote->getId();
		}
		else {
			$quoteId = null;
		}

		Springbot_Boss::addTrackable(
			$this->getRequest()->getParam('type'),
			$this->getRequest()->getParam('trackable'),
			$quoteId,
			$customerId
		);
	}


	private function viewLogCaller()
	{
		if ($this->hasSbAuthToken()) {
			$logName = $this->getRequest()->getParam('view_log');
			$logName = str_replace('..', '', $logName);
			$logPath = Mage::getBaseDir('log') . DS . $logName;
			if (!is_file($logPath) || !is_readable($logPath)) {
				$this->getResponse()->setHeader('Content-type', 'application/json');
				$this->getResponse()->setBody('{"success": false}');
			}
			else {
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
	}






}
