<?php
/**
 * Visitor Shadow Event Listener
 *
 * @version		v1.0.1 - 12/28/2012
 *
 * @category    Magento Intergations
 * @package     springbot
 * @author 		William Seitz
 * @division	SpringBot Integration Team
 * @support		magentosupport@springbot.com
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
class Springbot_Shadow_Model_Listeners_Observer	{

	const TOKEN_DELIMITER          = '%7';
	const COOKIE_NAME              = 'springbot_redirect_queue';
	const SB_TRACKABLES_COOKIE     = '_sbtk';
	const MAXIMUM_IDS_SAVED        = 32;

	/**
	 * Order of priority desc - "there can only be one!"
	 */
	protected $_redirectIds = array(
		'sb',
		'redirect_mongo_id',
	);

	public function post($observer)
	{
		try {
			Mage::helper('combine')->setLastCategoryId();
		} catch (Exception $e) {
			Mage::logException($e);
		}
	}

	public function escort($observer)
	{
		if ($quoteId = Mage::app()->getRequest()->getParam('quote_id')) {
			$suppliedSecurityHash = Mage::app()->getRequest()->getParam('sec_key');
			Mage::helper('combine/cart')->setQuote($quoteId, $suppliedSecurityHash);
		}

		try {
			// Set springbot redirect queue
			if($param = $this->_getParam()) {
				$this->_setSpringbotRedirectQueueCookie($param);
			}

			// Set sb trackables cookie
			if($trackables = $this->getTrackables()) {
				$this->_setSbTrackablesCookie($trackables);
			}

			$storeId = Mage::app()->getStore()->getStoreId();

			$this->runHealthcheck($storeId);

			$this->runQueueCleanup();

		}  catch (Exception $e)  {
			Mage::logException($e);
		}
		return;
	}

	public function runQueueCleanup()
	{
		// global scope, no need for store_id
		// run every hour
		if (Springbot_Shadow_Model_Timer::fire('cleanup', 0, 60)) {
			Springbot_Boss::internalCallback('work:cleanup');
		}
	}

	public function runHealthcheck($storeId)
	{
		if (Springbot_Shadow_Model_Timer::fire('healthcheck', $storeId)) {
			$this->querySpringbot($storeId);
			if (!Springbot_Boss::isCron()) {
				Springbot_Boss::startWorkManager();
			}
		}
	}

	public function getTrackables()
	{
		$params = $this->_getParams();
		$origParams = Mage::helper('combine/trackable')->getTrackables();
		$sbParams = $origParams ? clone $origParams : new stdClass();

		foreach($params as $param => $value) {
			if(preg_match('/^sb_/', $param)) {
				Springbot_Log::debug("Assigning $param from url with $value");
				$sbParams->$param = $value;
			}
		}
		$this->_ensureHttpReferer($sbParams);
		return !Mage::helper('combine')->isEmpty($sbParams) && $sbParams != $origParams ? $sbParams : false;
	}

	protected function _getParam()
	{
		$params = Mage::app()->getRequest()->getParams();
		foreach($this->_redirectIds as $id) {
			if(isset($params[$id])) {
				return $params[$id];
			}
		}
	}

	protected function _setSbTrackablesCookie($params)
	{
		if(!Mage::helper('combine')->isEmpty($params)) {
			$encoded = base64_encode(json_encode($params));
			$this->_setCookie(self::SB_TRACKABLES_COOKIE, $encoded);
		}
	}

	protected function _ensureHttpReferer(&$params)
	{
		if(!isset($params->sb_referer_host) && !$this->_hasSbReferHost()) {
			if(isset($_SERVER['HTTP_REFERER']) && isset($_SERVER['HTTP_HOST'])) {
				$host = $_SERVER['HTTP_HOST'];
				$parsed = parse_url($_SERVER['HTTP_REFERER']);
				$referer = isset($parsed['host']) ? $parsed['host'] : null;

				if($referer != $host) {
					Springbot_Log::debug("refered by $referer");
					if($host) {
						$params->sb_referer_host = $referer;
					}
				}
			}
		}
	}

	protected function _hasSbReferHost()
	{
		$params = Mage::helper('combine/trackable')->getTrackables();
		return isset($params->sb_referer_host);
	}

	protected function _setSpringbotRedirectQueueCookie($param)
	{
		$redirectQueue = Mage::getModel('core/cookie')->get(self::COOKIE_NAME);
		$cookieValues  = explode(self::TOKEN_DELIMITER,$redirectQueue);

		if (count($cookieValues) >= self::MAXIMUM_IDS_SAVED) {
			$oldestValue = array_shift($cookieValues);
		}

		if (end($cookieValues) != $param) {
			$cookieValues[] = $param;
		}

		$redirectQueue = implode(self::TOKEN_DELIMITER, $cookieValues);

		$this->_setCookie(self::COOKIE_NAME, $redirectQueue);
	}

	protected function _setCookie($name, $value)
	{
		Springbot_Log::debug("Saving cookie $name : $value");

		Mage::getModel('core/cookie')->set(
			$name,
			$value,
			strtotime('+365 days'),
			'/', // path
			null, // domain
			null, // secure
			false // httpOnly
		);
	}

	/**
	 * Query Springbot api healthcheck when running
	 * in standard / not cron mode.
	 */
	private function querySpringbot($storeId)
	{
		Springbot_Boss::internalCallback('cmd:healthcheck', array('s' => $storeId));
	}

	protected function _getParams()
	{
		return Mage::app()->getRequest()->getParams();
	}

	protected function _isCron()
	{
		return Springbot_Boss::isCron();
	}
}
