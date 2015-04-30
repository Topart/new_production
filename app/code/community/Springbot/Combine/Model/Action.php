<?php

class Springbot_Combine_Model_Action extends Springbot_Combine_Model_Cron
{
	public function _construct()
	{
		$this->_init('combine/action');
	}

	public function toAction()
	{
		$actionMethod = "_{$this->getType()}ToAction";
		return $this->{$actionMethod}();
	}

	public function isValidView()
	{
		return ($this->getType() == 'view') && $this->hasSku();
	}

	protected function _viewToAction()
	{
		return array(
			'action' => $this->getType(),
			'page_url' => $this->getPageUrl(),
			'sku' => $this->getSku(),
			'visitor_ip' => $this->getVisitorIp(),
			'category_id' => $this->getCategoryId(),
			'store_id' => $this->getStoreId(),
			'quantity' => $this->getQuantity(),
			'datetime' => $this->getCreatedAt(),
		);
	}

	protected function _atcToAction()
	{
		return array(
			'action' => $this->getType(),
			'sku' => $this->getSku(),
			'sku_fulfillment' => $this->getSkuFulfillment(),
			'quote_id' => $this->getQuoteId(),
			'category_id' => $this->getCategoryId(),
			'store_id' => $this->getStoreId(),
			'quantity' => $this->getQuantity(),
			'datetime' => $this->getCreatedAt(),
		);
	}

	protected function _purchaseToAction()
	{
		return array(
			'action' => $this->getType(),
			'sku' => $this->getSku(),
			'sku_fulfillment' => $this->getSkuFulfillment(),
			'purchase_id' => $this->getPurchaseId(),
			'category_id' => $this->getCategoryId(),
			'store_id' => $this->getStoreId(),
			'quantity' => $this->getQuantity(),
			'datetime' => $this->getCreatedAt(),
		);
	}
}
