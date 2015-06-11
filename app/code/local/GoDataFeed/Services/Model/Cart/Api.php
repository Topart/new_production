<?php
// MAGENTO 1.6.2 (Mage_Checkout_Model_Cart_Api)
// Modifications :
// - Cart_Api not present in mage 1.4.0.1
// - extends
// - method name (customCreate, customCreateOrder)
class GoDataFeed_Services_Model_Cart_Api extends GoDataFeed_Services_Model_Api_Resource_Checkout
{

	public function __construct()
	{
		$this->_storeIdSessionField = "cart_store_id";
		$this->_attributesMap['quote'] = array('quote_id' => 'entity_id');
		$this->_attributesMap['quote_customer'] = array('customer_id' => 'entity_id');
		$this->_attributesMap['quote_address'] = array('address_id' => 'entity_id');
		$this->_attributesMap['quote_payment'] = array('payment_id' => 'entity_id');
	}

	public function customCreate($store = null)
	{
		$storeId = $this->_getStoreId($store);

		try {
			/*@var $quote Mage_Sales_Model_Quote*/
			$quote = Mage::getModel('sales/quote');
			$quote->setStoreId($storeId)
					->setIsActive(false)
					->setIsMultiShipping(false)
					->save();
		} catch (Mage_Core_Exception $e) {
			$this->_fault('create_quote_fault', $e->getMessage());
		}
		return (int)$quote->getId();
	}

	// MAGENTO 1.6.?
	// Modifications :
	// - lines modified (*1*), (*2*), (*3*), (*4*)
	public function customCreateOrder($quoteId, $shippingFees, $shippingMethodDescription, $orderItems, $store = null, $agreements = null)
	{
		$requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds();
		if (!empty($requiredAgreements)) {
			$diff = array_diff($agreements, $requiredAgreements);
			if (!empty($diff)) {
				$this->_fault('required_agreements_are_not_all');
			}
		}

		$quote = $this->_getQuote($quoteId, $store);
		if ($quote->getIsMultiShipping()) {
			$this->_fault('invalid_checkout_type');
		}

		// *3* : Mage_Checkout_Model_Api_Resource_Customer not available in Magento 1.4
//		if ($quote->getCheckoutMethod() == Mage_Checkout_Model_Api_Resource_Customer::MODE_GUEST
//				&& !Mage::helper('checkout')->isAllowedGuestCheckout($quote, $quote->getStoreId())
		if ($quote->getCheckoutMethod() == GoDataFeed_Services_Model_Api_Resource_Customer::MODE_GUEST
				&& !Mage::helper('checkout')->isAllowedGuestCheckout($quote, $quote->getStoreId())
		) {
			$this->_fault('guest_checkout_is_not_enabled');
		}

		/** @var $customerResource Mage_Checkout_Model_Api_Resource_Customer */
		// *4* Mage_Checkout_Model_Api_Resource_Customer not available in Magento 1.4
		$customerResource = new GoDataFeed_Services_Model_Api_Resource_Customer();
//		$customerResource = Mage::getModel("checkout/api_resource_customer");
		$isNewCustomer = $customerResource->prepareCustomerForQuote($quote);

		try {
			$quote->collectTotals();

			/** @var $service Mage_Sales_Model_Service_Quote */
			$service = new GoDataFeed_Services_Model_Service_Quote($quote); // *1* overriding Mage_Sales_Model_Service_Quote
			$service->customSubmitAll($shippingFees, $shippingMethodDescription, $orderItems); // *2* method rename

			if ($isNewCustomer) {
				try {
					$customerResource->involveNewCustomer($quote);
				} catch (Exception $e) {
					Mage::logException($e);
				}
			}

			$order = $service->getOrder();
			if ($order) {

				Mage::dispatchEvent('checkout_type_onepage_save_order_after',
					array('order' => $order, 'quote' => $quote));

				try {
					$order->sendNewOrderEmail();
				} catch (Exception $e) {
					Mage::logException($e);
				}
			}

			Mage::dispatchEvent(
				'checkout_submit_all_after',
				array('order' => $order, 'quote' => $quote)
			);
		} catch (Mage_Core_Exception $e) {
			$this->_fault('create_order_fault', $e->getMessage());
		}

		return $order->getIncrementId();
	}
}
