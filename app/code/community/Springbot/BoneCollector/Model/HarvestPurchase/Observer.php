<?php
/**
 * Visitor BoneCollector Event Listener
 *
 * @version		v1.0.0 - 12/28/2012
 *
 * @category    Magento Integrations
 * @package     springbot
 * @author 		William Seitz
 * @division	SpringBot Integration Team
 * @support		magentosupport@springbot.com
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
class Springbot_BoneCollector_Model_HarvestPurchase_Observer extends Springbot_BoneCollector_Model_HarvestAbstract
{
	const METHOD                 = 'purchase';
	const COOKIE_NAME            = 'springbot_redirect_queue';
	const STDLIST_BASENAME       = 'SpringbotServices-BoneCollector-Purchase';

	protected $_order;

	public function purchaseHarvest($observer)
	{
		if($this->_getCheckoutSession()->getSpringbotLogPurchaseAction() === true) {
			$this->callPurchaseLogAction($observer);
			$this->_getCheckoutSession()->unsSpringbotLogPurchaseAction();
		}

		if($this->_doSendPurchase($observer)) {
			$this->_purchaseHarvest($observer, true);
		}
	}

	public function purchaseHarvestAdmin($observer)
	{
		$this->_purchaseHarvest($observer, false);
	}

	public function purchaseLogAction($observer)
	{
		$this->_initObserver($observer);
		$this->_getCheckoutSession()->setSpringbotLogPurchaseAction(true);
	}

	public function callPurchaseLogAction($observer)
	{
		try {
			$this->_initObserver($observer);
			$this->_order = $observer->getEvent()->getOrder();

			Springbot_Boss::scheduleJob(
				'log:purchase',
				array(
					'i' => $this->_order->getId(),
					'c' => $this->_getLastCategory(),
				),
				Springbot_Services_Priority::LISTENER, 'listener'
			);

		}  catch (Exception $e)  {
			Mage::logException($e);
		}
	}

	protected function _purchaseHarvest($observer, $frontend = true)
	{
		try {
			$this->_initObserver($observer);
			$this->_order = $observer->getEvent()->getOrder();
			$this->updateTrackables();

			if($frontend) {
				$this->_logUserAgent();
			}
			Springbot_Boss::scheduleJob('post:purchase',
				array(
					'i' => $this->_order->getEntityId(),
					'c' => $this->_getLastCategory(),
					'r' => $this->getRedirectIds($frontend),
				),
				Springbot_Services_Priority::LISTENER, 'listener'
			);

		}  catch (Exception $e)  {
			Mage::logException($e);
		}
	}

	protected function _logUserAgent() {
		Mage::helper('combine/trackable')->addTrackable(
			$this->_order->getCustomerEmail(),
			'purchase_user_agent',
			$_SERVER['HTTP_USER_AGENT'],
			$this->_order->getQuoteId(),
			$this->_order->getCustomerId()
		);
	}

	public function updateTrackables()
	{
		$helper  = Mage::helper('combine/trackable');
		$params  = $helper->getTrackables();
		$quoteId = $this->_order->getQuoteId();

		foreach($this->getTrackablesForQuote($quoteId) as $trackable) {
			$trackable->setOrderId($this->_order->getId())
				->setCustomerId($this->_order->getCustomerId())
				->save();
		}
	}

	public function getTrackablesForQuote($quoteId)
	{
		return Mage::getModel('combine/trackable')->getCollection()
			->addFieldToFilter('quote_id', $quoteId);
	}

	protected function _doSendPurchase($observer)
	{
		$order   = $observer->getEvent()->getOrder();
		$hash    = sha1($order->toJson());
		$session = $this->_getCheckoutSession();

		if($session->getSpringbotOrderHash() == $hash) {
			Springbot_Log::debug("Purchase hash is match, this object has already been posted, skipping");
			return false;
		} else {
			$session->setSpringbotOrderHash($hash);
			Springbot_Log::debug("Purchase hash does not match cache, sending purchase");
			return true;
		}
	}

	protected function _getCustomerEmail()
	{
		return $this->_order->getCustomerEmail();
	}

	protected function _getLastCategory()
	{
		return Mage::helper('combine')->getLastCategoryId();
	}

	protected function _getCheckoutSession()
	{
		return Mage::getSingleton('checkout/session');
	}

	public function getRedirectIds($frontend = true)
	{
		$redirects = $frontend ? Mage::helper('combine/redirect')->getRedirectIds() : array();

		if($dbRedirects = Mage::helper('combine/redirect')->getRedirectsByEmail($this->_getCustomerEmail(), $this->_order->getCreatedAt())) {
			$redirects = array_unique(array_merge($redirects, $dbRedirects));
		}

		return array_values($redirects);
	}
}
