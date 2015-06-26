<?php

class GoDataFeed_Services_Model_Service_Quote extends Mage_Sales_Model_Service_Quote
{
	// MAGENTO 1.6.? (Mage_Sales_Model_Service_Quote)
	// Modifications :
	// - lines commented *1*
	protected function _validate()
	{
		$helper = Mage::helper('sales');
		if (!$this->getQuote()->isVirtual()) {
			$address = $this->getQuote()->getShippingAddress();
			$addressValidation = $address->validate();
			if ($addressValidation !== true) {
				Mage::throwException(
					$helper->__('Please check shipping address information. %s', implode(' ', $addressValidation))
				);
			}

			// *1* **********************************************************************
			// COMMENTED FROM ORIGINAL CODE TO AVOID VALIDATING THE SHIPPING METHOD
			//			$method = $address->getShippingMethod();
			//			$rate = $address->getShippingRateByCode($method);
			//			if (!$this->getQuote()->isVirtual() && (!$method || !$rate)) {
			//				Mage::throwException($helper->__('Please specify a shipping method.'));
			//			}
		}

		$addressValidation = $this->getQuote()->getBillingAddress()->validate();
		if ($addressValidation !== true) {
			Mage::throwException(
				$helper->__('Please check billing address information. %s', implode(' ', $addressValidation))
			);
		}

		if (!($this->getQuote()->getPayment()->getMethod())) {
			Mage::throwException($helper->__('Please select a valid payment method.'));
		}

		return $this;
	}

	// MAGENTO 1.6.? (Mage_Sales_Model_Service_Quote)
	// Modifications :
	// - method name renamed
	// - *1* commented line
	// - method name renamed *2*
	public function customSubmitAll($shippingFees, $shippingMethodDescription, $orderItems)
	{
		// don't allow submitNominalItems() to inactivate quote
		$shouldInactivateQuoteOld = $this->_shouldInactivateQuote;
		$this->_shouldInactivateQuote = false;
		try {

			// *1*
			// submitNominalItems is not available in 1.4 and seems to take care of features not available at that time
			//			$this->submitNominalItems();
			$this->_validate();


			$this->_shouldInactivateQuote = $shouldInactivateQuoteOld;
		} catch (Exception $e) {
			$this->_shouldInactivateQuote = $shouldInactivateQuoteOld;
			throw $e;
		}
		// no need to submit the order if there are no normal items remained
		if (!$this->_quote->getAllVisibleItems()) {
			$this->_inactivateQuote();
			return;
		}

		// *2*
		$this->customSubmitOrder($shippingFees, $shippingMethodDescription, $orderItems);
	}

	// MAGENTO 1.6.? (Mage_Sales_Model_Service_Quote)
	// Modifications :
	// - method name renamed
	// - lines added *1*
	// - line commented *2* *3*
	// - *5*
	// - *4*
	// - *6* new variable
	public function customSubmitOrder($shippingFees, $shippingMethodDescription, $orderItems)
	{
		// *2*
		// _deleteNominalItems is not available in 1.4 and seems to take care of features not available at that time
		//		$this->_deleteNominalItems(); // ORIGINAL LINE
		$this->_validate();
		$quote = $this->_quote;
		$isVirtual = $quote->isVirtual();

		$transaction = Mage::getModel('core/resource_transaction');
		if ($quote->getCustomerId()) {
			$transaction->addObject($quote->getCustomer());
		}
		$transaction->addObject($quote);

		$quote->reserveOrderId();
		if ($isVirtual) {
			$order = $this->_convertor->addressToOrder($quote->getBillingAddress());
		} else {
			$order = $this->_convertor->addressToOrder($quote->getShippingAddress());
		}
		$order->setBillingAddress($this->_convertor->addressToOrderAddress($quote->getBillingAddress()));
		if ($quote->getBillingAddress()->getCustomerAddress()) {
			$order->getBillingAddress()->setCustomerAddress($quote->getBillingAddress()->getCustomerAddress());
		}
		if (!$isVirtual) {
			$order->setShippingAddress($this->_convertor->addressToOrderAddress($quote->getShippingAddress()));
			if ($quote->getShippingAddress()->getCustomerAddress()) {
				$order->getShippingAddress()->setCustomerAddress($quote->getShippingAddress()->getCustomerAddress());
			}
		}

		// *4*
		// overrides Mage_Sales_Model_Order_Payment with GoDataFeed_Services_Model_Order_Payment
		// allows to return the custom payment method GoDataFeed_Services_Model_Method_OrderSync
		// which disables validation
		//		$order->setPayment($this->_convertor->paymentToOrderPayment($quote->getPayment())); // ORIGINAL LINE
		$orderPayment = $this->_convertor->paymentToOrderPayment($quote->getPayment());
		$customOrderPayment = new GoDataFeed_Services_Model_Order_Payment();
		$customOrderPayment->setData($orderPayment->getData());

		// code taken from order->addPayment()
		// modified here to allow GoDataFeed_Services_Model_Order_Payment classes (**) to be added to the order payment collection
		$customOrderPayment->setOrder($order)->setParentId($order->getId());
		if (!$customOrderPayment->getId()) {
			$paymentsCollection = $order->getPaymentsCollection();
			$paymentsCollection->setItemObjectClass("GoDataFeed_Services_Model_Order_Payment"); // (**)
			$paymentsCollection->addItem($customOrderPayment);
		}
		// end of modification *4*

		foreach ($this->_orderData as $key => $value) {
			$order->setData($key, $value);
		}

		$overridedSubtotal = 0; // *6*

		foreach ($quote->getAllItems() as $item) {
			$orderItem = $this->_convertor->itemToOrderItem($item);
			if ($item->getParentItem()) {
				$orderItem->setParentItem($order->getItemByQuoteItemId($item->getParentItem()->getId()));
			}

			// *5* *****************************************************
			// CODE ADDED TO MANAGE CUSTOM PRODUCT PRICES
			foreach($orderItems as $overridedOrderItem) {

				$itemSku = $item->getSku();
				if($overridedOrderItem['sku'] == $itemSku) {

					$originalPrice = $item->getOriginalPrice();
					$orderItem->setOriginalPrice($originalPrice);
					$orderItem->setBaseOriginalPrice($overridedOrderItem);

					$overridedPrice = $overridedOrderItem['price'];
					$overridedRowTotal = $overridedPrice * $overridedOrderItem['qty'];
					$overridedSubtotal += $overridedRowTotal;

					$orderItem->setPrice($overridedPrice);
					$orderItem->setBasePrice($overridedPrice);

					$orderItem->setRowTotal($overridedRowTotal);
					$orderItem->setBaseRowTotal($overridedRowTotal);

					break;
				}
			}
			// END OF *5*
			// *******************************************************

			$order->addItem($orderItem);
		}

		// *1* *****************************************************
		// CODE ADDED TO MANAGE CUSTOM SHIPPING/PRODUCT COSTS
		$order->setShippingMethod('flatrate'); // Needed for Magento 1.6 otherwise the shipment page breaks
		$total = $overridedSubtotal + $shippingFees;
		$order->setBaseShippingAmount($shippingFees);
		$order->setShippingAmount($shippingFees);
		$order->setBaseShippingInclTax($shippingFees);
		$order->setShippingInclTax($shippingFees);


		$order->setBaseGrandTotal($total);
		$order->setGrandTotal($total);

		$order->setBaseSubtotal($overridedSubtotal);
		$order->setSubtotal($overridedSubtotal);
		$order->setBaseSubtotalInclTax($overridedSubtotal);
		$order->setSubtotalInclTax($overridedSubtotal);

		$order->setBaseTotalDue($total);
		$order->setTotalDue($total);

		$order->setShippingDescription($shippingMethodDescription);
		// END OF *1*
		// *******************************************************

		$order->setQuote($quote);

		$transaction->addObject($order);
		$transaction->addCommitCallback(array($order, 'place'));
		$transaction->addCommitCallback(array($order, 'save'));

		/**
		 * We can use configuration data for declare new order status
		 */
		Mage::dispatchEvent('checkout_type_onepage_save_order', array('order' => $order, 'quote' => $quote));
		Mage::dispatchEvent('sales_model_service_quote_submit_before', array('order' => $order, 'quote' => $quote));
		try {
			$transaction->save();
			//			$this->_inactivateQuote(); *3* Not available in magento 1.4
			Mage::dispatchEvent('sales_model_service_quote_submit_success', array('order' => $order, 'quote' => $quote));
		} catch (Exception $e) {

			if (!Mage::getSingleton('customer/session')->isLoggedIn()) {
				// reset customer ID's on exception, because customer not saved
				$quote->getCustomer()->setId(null);
			}

			//reset order ID's on exception, because order not saved
			$order->setId(null);
			/** @var $item Mage_Sales_Model_Order_Item */
			foreach ($order->getItemsCollection() as $item) {
				$item->setOrderId(null);
				$item->setItemId(null);
			}

			Mage::dispatchEvent('sales_model_service_quote_submit_failure', array('order' => $order, 'quote' => $quote));
			throw $e;
		}
		Mage::dispatchEvent('sales_model_service_quote_submit_after', array('order' => $order, 'quote' => $quote));
		$this->_order = $order;
		return $order;
	}

	// MAGENTO 1.6.? (Mage_Sales_Model_Service_Quote)
	// Modifications :
	// - none
	public function getOrder()
	{
		return $this->_order;
	}
}
