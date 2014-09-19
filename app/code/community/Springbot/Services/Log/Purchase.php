<?php

class Springbot_Services_Log_Purchase extends Springbot_Services_Abstract
{
	public function run()
	{
		$orderId = $this->getEntityId();
		Springbot_Log::debug("Log purchase action for order_id : $orderId");
		$purchase = Mage::getModel('sales/order')->load($orderId);

		if(!$purchase->getId()) {
			// @TODO create remote error logger?
			throw new Exception("Purchase record not available in database!");
		}

		foreach($purchase->getAllVisibleItems() as $item) {
			$this->_logEvent('purchase', array(
				$this->_getAccessibleSku($item),
				$item->getSku(),
				$purchase->getIncrementId(),
				$purchase->getStoreId(),
				Mage::helper('combine')->checkCategoryIdSanity($this->_getCategoryId(), $item->getProductId()),
			));
		}
		return;
	}

	protected function _logEvent($action, $content)
	{
		$eventDatetime  = date(Springbot_Boss::DATE_FORMAT);
		$logContent = array($action, $eventDatetime);
		$fHandle = fopen(Springbot_Boss::getEventHistoryFilename(), 'a');

		fputcsv($fHandle, array_merge($logContent, $content));
		fclose ($fHandle);
	}


	protected function _getCategoryId()
	{
		if($this->hasCategoryId()) {
			return $this->getCategoryId();
		}
		return null;
	}

	protected function _getAccessibleSku($item)
	{
		return Mage::helper('combine/parser')->getAccessibleSkuFromSalesItem($item);
	}
}
