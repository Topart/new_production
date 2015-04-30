<?php

class Springbot_BoneCollector_Model_HarvestCart_Observer extends Springbot_BoneCollector_Model_HarvestAbstract
{
	/**
	 * Push cart object to api
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function onFrontendQuoteSaveAfter($observer)
	{
		try {
			$this->_initObserver($observer);
			$quoteObject = $observer->getQuote();
			$quoteParser = $this->_initParser($quoteObject);

			if (
				$quoteParser->getItemsCount() > 0 &&
				($quoteParser->hasCustomerData() || Mage::getStoreConfig('springbot/config/send_cart_noemail')) &&
				$quoteParser->getStoreId()
			) {
				$json = $quoteParser->toJson();

				if (Mage::helper('combine')->doSendQuote($json)) {
					Springbot_Boss::addTrackable(
						'cart_user_agent',
						$_SERVER['HTTP_USER_AGENT'],
						$quoteParser->getQuoteId(),
						$quoteParser->getCustomerId(),
						$quoteParser->getCustomerEmail()
					);

					Springbot_Boss::scheduleJob('post:cart', array(
							's' => Mage::app()->getStore()->getId(),
							'i' => $quoteParser->getQuoteId()
						), Springbot_Services::LISTENER, 'listener'
					);

					$this->insertRedirectIds($quoteParser);
					$this->createTrackables($quoteParser);
				}
			}
		}
		catch (Exception $e) {
			Springbot_Log::error($e);
		}
	}

	/**
	 * Capture sku for add to cart
	 * Inserts line into event csv to push
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function onFrontendCartAddProduct($observer)
	{
		try {
			$this->_initObserver($observer);
			$quoteId = Mage::getSingleton("checkout/session")->getQuote()->getId();
			$storeId = Mage::app()->getStore()->getStoreId();
			$product = $observer->getEvent()->getProduct();
			$lastCatId = Mage::helper('combine')->checkCategoryIdSanity($this->_getLastCategory(), $storeId);
			$visitorIp = Mage::helper('core/http')->getRemoteAddr(true);

			// Check added qty from request, default to 1
			$qtyAdded = $observer->getEvent()->getRequest()->getParam('qty');
			$qtyAdded = is_numeric($qtyAdded) && $qtyAdded > 0 ? $qtyAdded : 1;

			Springbot_Boss::insertEvent(array(
				'type' => 'atc',
				'sku' => $this->getTopLevelSku($observer),
				'sku_fulfillment' => $product->getSku(),
				'quote_id' => $quoteId,
				'category_id' => $lastCatId,
				'store_id' => $storeId,
				'quantity' => $qtyAdded,
			));
		}
		catch (Exception $e) {
			Springbot_Log::error($e);
		}
	}

	/**
	 * Add product actions on update qty
	 *
	 * This is a one-way street. This examines the new quantity to the saved
	 * quote item amount. If the new quanity is higher, it inserts one event
	 * for each item higher than the pre-existing quanity (i.e. the delta).
	 * We are not concerned with items getting removed as we are counting the
	 * add actions themselves.
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function onFrontendCartUpdate($observer)
	{
		try {
			$this->_initObserver($observer);
			$info = $observer->getEvent()->getInfo();
			$cart = $observer->getEvent()->getCart();
			$storeId = $cart->getQuote()->getStoreId();
			$parsedQuote = Mage::getModel('combine/parser_quote', $cart->getQuote());

			foreach ($info as $itemId => $itemInfo) {
				$item = $cart->getQuote()->getItemById($itemId);

				if($item && isset($itemInfo['qty'])) {
					if($itemInfo['qty'] > $item->getQty()) {
						$parsed = Mage::getModel('combine/parser_quote_item', $item);
						$diffQty = $itemInfo['qty'] - $item->getQty();
						$diffQty = $diffQty > 0 ? $diffQty : 1;

						Springbot_Log::debug("Cart delta +$diffQty");
						Springbot_Boss::insertEvent(array(
							'type' => 'atc',
							'sku' => $parsed->getSku(),
							'sku_fulfillment' => $parsed->getSkuFulfillment(),
							'quote_id' => $parsedQuote->getQuoteId(),
							'category_id' => $this->_getLastCategory(),
							'store_id' => $storeId,
							'quantity' => $diffQty,
						));
					}
				}
			}
		}
		catch (Exception $e) {
			Springbot_Log::error($e);
		}
	}

	/**
	 * This exists as a naive dependency injector, so we can set the
	 * local object for testing purposes
	 *
	 * @param $quote Mage_Sales_Model_Quote
	 * @return Springbot_Combine_Model_Parser_Quote
	 */
	protected function _initParser($quote)
	{
		if (!isset($this->_parser)) {
			$this->_parser = Mage::getModel('Springbot_Combine_Model_Parser_Quote', $quote);
		}
		return $this->_parser;
	}

	public function insertRedirectIds($quote)
	{
		if (Mage::helper('combine/redirect')->hasRedirectId()) {
			Springbot_Log::debug("Insert redirect id for customer : {$quote->getCustomerEmail()}");
			$params = array(
				'email' => $quote->getCustomerEmail(),
				'quote_id' => $quote->getQuoteId(),
				'customer_id' => $quote->getCustomerId(),
			);

			Mage::helper('combine/redirect')->insertRedirectIds($params);
		}
	}

	public function createTrackables($quoteParser)
	{
		$helper = Mage::helper('combine/trackable');
		$model = Mage::getModel('combine/trackable');

		if ($helper->hasTrackables()) {
			foreach ($helper->getTrackables() as $type => $value) {
				$model->setData(array(
					'email' => $quoteParser->getCustomerEmail(),
					'type' => $type,
					'value' => $value,
					'quote_id' => $quoteParser->getQuoteId(),
					'customer_id' => $quoteParser->getCustomerId(),
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
