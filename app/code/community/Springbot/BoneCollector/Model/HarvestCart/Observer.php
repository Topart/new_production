<?php
/**
 * BoneCollector Event Listener (AddToCart SKU Harvest)
 *
 * @version     v1.0.0 - 4/19/2013
 *
 * @category    Magento Integrations
 * @package     springbot
 * @author      William Seitz
 * @division    SpringBot Integration Team
 * @support     magentosupport@springbot.com
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
class Springbot_BoneCollector_Model_HarvestCart_Observer extends Springbot_BoneCollector_Model_HarvestAbstract
{
	const ACTION_METHOD = 'atc';

	/**
	 * This exists as a naive dependency injector, so we can set the
	 * local object for testing purposes
	 *
	 * @param $quote Mage_Sales_Model_Quote
	 * @return Springbot_Combine_Model_Parser_Quote
	 */
	protected function _initParser($quote)
	{
		if(!isset($this->_parser)) {
			$this->_parser = Mage::getModel('Springbot_Combine_Model_Parser_Quote', $quote);
		}
		return $this->_parser;
	}

	/**
	 * Push cart object to api
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function addToCartHarvest($observer)
	{
		try {
			$this->_initObserver($observer);
			$quoteObject = $observer->getQuote();
			$quote = $this->_initParser($quoteObject);

			if (
				$quote->getItemsCount() > 0 &&
				($quote->hasCustomerData() || Mage::getStoreConfig('springbot/config/send_cart_noemail')) &&
				$quote->getStoreId()
			) {
				$json = $quote->toJson();

				if(Mage::helper('combine')->doSendQuote($json)) {

					Mage::helper('combine/trackable')->addTrackable(
						$quote->getCustomerEmail(),
						'cart_user_agent',
						$_SERVER['HTTP_USER_AGENT'],
						$quote->getQuoteId(),
						$quote->getCustomerId()
					);

					Springbot_Boss::scheduleJob(
						'post:cart',
						array(
							's' => Mage::app()->getStore()->getId(),
							'i' => $quote->getQuoteId(),
							'r' => Mage::helper('combine/redirect')->getRawEscapedCookie()
						), Springbot_Services_Priority::LISTENER, 'listener'
					);

					$this->insertRedirectIds($quote);
					$this->createTrackables($quote);
				}
			}
		} catch (Exception $e) {
			Mage::logException($e);
		}
	}

	/**
	 * Capture sku for add to cart
	 * Inserts line into event csv to push
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function captureSku($observer)
	{
		$this->_initObserver($observer);
		$quoteId = Mage::getSingleton("checkout/session")->getQuote()->getId();

		$eventDatetime        = date("Y-m-d H:i:s");
		$openModeAppend       = 'a';
		$eventHistoryFilename = Mage::getBaseDir().'/var/log/Springbot-EventHistory.csv';

		try {
			$storeId       = Mage::app()->getStore()->getStoreId();
			$lastCatId     = $this->_getLastCategory();
			$fHandle       = fopen($eventHistoryFilename,$openModeAppend);
			$viewedMessage = array(
				self::ACTION_METHOD,
				$eventDatetime,
				$this->getTopLevelSku($observer),
				$quoteId,
				$storeId,
				Mage::helper('combine')->checkCategoryIdSanity($lastCatId, $observer->getEvent()->getProduct())
			);
			fputcsv($fHandle,$viewedMessage,',');
			fclose ($fHandle);

		}  catch (Exception $e)  {
			Mage::logException($e);
			Mage::log('Unknown exception opening '.$eventHistoryFilename);
		}
		return;
	}

	public function insertRedirectIds($quote)
	{
		if(Mage::helper('combine/redirect')->hasRedirectId()) {
			Springbot_Log::debug("Insert redirect id for customer : {$quote->getCustomerEmail()}");
			$params = array(
				'email' => $quote->getCustomerEmail(),
				'quote_id' => $quote->getQuoteId(),
				'customer_id' => $quote->getCustomerId(),
			);

			Mage::helper('combine/redirect')->insertRedirectIds($params);
		}
	}

	public function createTrackables($quote)
	{
		$helper = Mage::helper('combine/trackable');
		$model = Mage::getModel('combine/trackable');

		if($helper->hasTrackables()) {
			foreach($helper->getTrackables() as $type => $value) {
				$model->setData(array(
					'email' => $quote->getCustomerEmail(),
					'type' => $type,
					'value' => $value,
					'quote_id' => $quote->getQuoteId(),
					'customer_id' => $quote->getCustomerId(),
				));
				$model->createOrUpdate();
			}
		}
	}

	public function setParser($parser)
	{
		$this->_parser = $parser;
	}

	protected function _getLastCategory()
	{
		return Mage::helper('combine')->getLastCategoryId();
	}

}
