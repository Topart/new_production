<?php

class Springbot_Combine_Model_Parser_Purchase extends Springbot_Combine_Model_Parser
{
	protected $_accessor = '_purchase';
	protected $_purchase;
	protected $_lineItems;

	public function __construct(Mage_Sales_Model_Order $order)
	{
		$this->_lineItems = null;
		$this->_purchase = $order;
		$this->_parse();
	}

	protected function _parse()
	{
		$model = $this->_purchase;
		if ($model->getStoreId() == 0) {
			$storeId = Mage::getStoreConfig('springbot/config/store_zero_alias');
		}
		else {
			$storeId = $model->getStoreId();
		}

		$this->setData(array(
			'purchase_id' => $model->getIncrementId(),
			'entity_id' => $model->getId(),
			'email' => $this->_getEmail($model),
			'quote_id' => $model->getQuoteId(),
			'redirect_mongo_id' => $this->_getRedirectMongoId(),
			'redirect_mongo_ids' => $this->_getRedirectMongoIds(),
			'sb_params' => $this->_getSbParams($model->getQuoteId()),
			'store_id' => $this->_getSpringbotStoreId($storeId),
			'customer_id' => $this->_getCustomerId(),
			'order_date_time' => $model->getCreatedAt(),
			'order_gross' => $this->_getBaseAmt('grand_total'),
			'order_discount' => $this->_getBaseAmt('discount_amount'),
			'order_paid' => $this->_getOrderPaid(),
			'shipping' => $this->_getBaseAmt('shipping_amount'),
			'sales_tax' => $this->_getBaseAmt('tax_amount'),
			'pay_method' => $this->_getPaymentMethod(),
			'guest' => $model->getCustomerIsGuest() ? 'Y' : 'N',
			'order_state' => $model->getState(),
			'order_status' => $model->getStatus(),
			'line_items' => $this->_getLineItems(),
			'attribute_items' => $this->_getAttributeArray(),
			'json_data' => $this->_getJsonData(),
		));

		return parent::_parse();
	}

	protected function _getRedirectMongoId()
	{
		if($modelId = $this->_purchase->getRedirectMongoId()) {
			return $modelId;
		}
		return Mage::helper('combine/redirect')->getRedirectByOrderId($this->_purchase->getId())->getRedirectId();
	}

	protected function _getRedirectMongoIds()
	{
		if($modelIds = $this->_purchase->getRedirectMongoIds()) {
			return array_values($modelIds);
		}
		return Mage::helper('combine/redirect')->getRedirectsByEmail($this->_purchase->getCustomerEmail(), $this->_purchase->getCreatedAt());
	}

	protected function _getSbParams($quoteId)
	{
		return Mage::helper('combine/trackable')->getTrackablesHashByQuote($quoteId);
	}

	protected function _getLineItems()
	{
		if(!isset($this->_lineItems)) {
			$lineItems = array();
			$items = $this->_purchase->getAllVisibleItems();

			if(count($items) > 0) {
				foreach($items as $item) {
					$lineItems[] = Mage::getModel('combine/parser_purchase_item', $item)->getData();
				}
			}
			$this->_lineItems = $lineItems;
		}
		return $this->_lineItems;
	}

	protected function _getAttributeArray()
	{
		$attrs = array();
		foreach($this->_lineItems as $item)
		{
			$id = isset($item['attribute_set_id']) ? $item['attribute_set_id'] : null;
			foreach($item['attributes'] as $key => $value) {
				if(!empty($value)) {
					$attrs[] = "$key:$value:$id";
				}
			}
		}
		return $attrs;
	}

	protected function _getJsonData()
	{
		$model = $this->_purchase;

		return array(
			'order_dow' => date('D', strtotime($model->getCreatedAt())),
			'currency' => $model->getOrderCurrencyCode(),
			'ship_service' => $model->getShippingDescription(),
			'ip' => $model->getRemoteIp(),
			'gift_msg' => (bool) $model->getGiftMessage(),
			'guest' => (bool) $model->getCustomerIsGuest(),
			'free_shipping' => (bool) $model->getFreeShipping(),
			'coupon_code' => $model->getCouponCode(),
		);
	}

	protected function _getCustomerId()
	{
		if($id = $this->_purchase->getCustomerId()) {
			return $id;
		} else {
			$offset = Springbot_Combine_Model_Parser_Guest::OFFSET;
			return $offset + $this->_purchase->getEntityId();
		}
	}

	protected function _getOrderPaid()
	{
		$payment = $this->_getPayment();
		if($amt = $payment->getBaseAmountOrdered()) {
			// using payment amt ordered
		} else if ($amt = $payment->getBaseAmountAuthorized()) {
			// using payment authorized amt
		} else {
			$amt = $this->_getBaseAmt('grand_total');
		}
		return $amt;
	}

	protected function _getPaymentMethod()
	{
		$payment = $this->_getPayment();
		return is_object($payment) ? $payment->getMethod() : null;
	}

	protected function _getPayment()
	{
		$payment = $this->_purchase->getPayment();
		if(empty($payment)) {
			$payment = Mage::getModel('sales/order_payment');
		}
		return $payment;
	}

	private function _getEmail($model)
	{
		$email = $model->getCustomerEmail();

		return $email;
	}
}
