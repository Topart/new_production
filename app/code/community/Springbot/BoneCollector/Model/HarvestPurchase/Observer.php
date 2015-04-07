<?php
class Springbot_BoneCollector_Model_HarvestPurchase_Observer extends Springbot_BoneCollector_Model_HarvestAbstract
{
	const ACTION = 'purchase';

	public function onFrontendOrderSaveAfter($observer)
	{
		try {
			$order = $observer->getEvent()->getOrder();
			Mage::helper('combine/trackable')->updateTrackables($order);
			Springbot_Boss::addTrackable(
				'purchase_user_agent',
				$_SERVER['HTTP_USER_AGENT'],
				$order->getQuoteId(),
				$order->getCustomerId(),
				$order->getCustomerEmail(),
				$order->getEntityId()
			);
			$this->_schedulePurchasePost($order, true);
		}
		catch (Exception $e) {
			Springbot_Log::error($e);
		}
	}

	public function onAdminOrderSaveAfter($observer)
	{
		try {
			$order = $observer->getEvent()->getOrder();
			Mage::helper('combine/trackable')->updateTrackables($order);
			$this->_schedulePurchasePost($order, false);
		}
		catch (Exception $e) {
			Springbot_Log::error($e);
		}
	}

	public function onFrontendOrderPlaceAfter($observer)
	{
		try {
			$this->_initObserver($observer);
			$order = $observer->getEvent()->getOrder();
			$storeId = $order->getStoreId();
			$parsedPurchase = Mage::getModel('combine/parser_purchase', $order);

			foreach ($order->getAllVisibleItems() as $item) {
				$lastCatId = $this->_getSaneCategoryId($item);
				$qty = $item->getQtyOrdered();
				$qty = $qty > 0 ? $qty : 1;

				$parsedItem = Mage::getModel('combine/parser_purchase_item', $item);

				Springbot_Boss::insertEvent(array(
					'type' => 'purchase',
					'sku' => $parsedItem->getSku(),
					'sku_fulfillment' => $parsedItem->getSkuFulfillment(),
					'purchase_id' => $parsedPurchase->getPurchaseId(),
					'category_id' => $this->_getSaneCategoryId($item),
					'store_id' => $storeId,
					'quantity' => $qty,
				));
			}
		}
		catch (Exception $e) {
			Springbot_Log::error($e);
		}
	}

	private function _schedulePurchasePost($order, $frontend)
	{
		Springbot_Boss::scheduleJob('post:purchase',
			array(
				'i' => $order->getEntityId(),
				'c' => $this->_getLastCategoryId(),
				'r' => $this->_getRedirectIds($frontend, $order),
			),
			Springbot_Services::LISTENER, 'listener'
		);
	}

	private function _getLastCategoryId()
	{
		return Mage::helper('combine')->getLastCategoryId();
	}

	private function _getSaneCategoryId($item)
	{
		return Mage::helper('combine')->checkCategoryIdSanity(
				$this->_getLastCategoryId(),
				$item->getProductId()
			);
	}

	private function _getAccessibleSku($item)
	{
		return Mage::helper('combine/parser')->getAccessibleSkuFromSalesItem($item);
	}

	private function _getRedirectIds($frontend = true, $order)
	{
		if ($frontend) {
			$redirects = Mage::helper('combine/redirect')->getRedirectIds();
		}
		else {
			$redirects = array();
		}

		$customerEmail = $order->getCustomerEmail();
		if ($dbRedirects = Mage::helper('combine/redirect')->getRedirectsByEmail($customerEmail, $order->getCreatedAt())) {
			$redirects = array_unique(array_merge($redirects, $dbRedirects));
		}
		return array_reverse(array_values($redirects));
	}
}
