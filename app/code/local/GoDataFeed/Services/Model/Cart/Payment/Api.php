<?php
// DUPLICATED CODE FROM MAGENTO (Mage_Checkout_Model_Cart_Product_Api)
// CODE VERSION : 1.6.2
// Modifications :
// - Cart_Payment_Api not present in mage 1.4.0.1
// - extends
// - setCustomPaymentMethod
class GoDataFeed_Services_Model_Cart_Payment_Api extends GoDataFeed_Services_Model_Api_Resource_Checkout
{

    protected function _preparePaymentData($data)
    {
        if (!(is_array($data) && is_null($data[0]))) {
            return array();
        }

        return $data;
    }

    /**
     * @param  $method
     * @param  $quote
     * @return bool
     */
    protected function _canUsePaymentMethod($method, $quote)
    {
        if ( !($method->isGateway() || $method->canUseInternal()) ) {
            return false;
        }

        if (!$method->canUseForCountry($quote->getBillingAddress()->getCountry())) {
            return false;
        }

        if (!$method->canUseForCurrency(Mage::app()->getStore($quote->getStoreId())->getBaseCurrencyCode())) {
            return false;
        }

        /**
         * Checking for min/max order total for assigned payment method
         */
        $total = $quote->getBaseGrandTotal();
        $minTotal = $method->getConfigData('min_order_total');
        $maxTotal = $method->getConfigData('max_order_total');

        if ((!empty($minTotal) && ($total < $minTotal)) || (!empty($maxTotal) && ($total > $maxTotal))) {
            return false;
        }

        return true;
    }

    protected function _getPaymentMethodAvailableCcTypes($method)
    {
        $ccTypes = Mage::getSingleton('payment/config')->getCcTypes();
        $methodCcTypes = explode(',',$method->getConfigData('cctypes'));
        foreach ($ccTypes as $code=>$title) {
            if (!in_array($code, $methodCcTypes)) {
                unset($ccTypes[$code]);
            }
        }
        if (empty($ccTypes)) {
            return null;
        }

        return $ccTypes;
    }

	// MAGENTO 1.6.2 (Mage_Checkout_Model_Cart_Product_Api)
	// Modifications :
	// - method name
	// - *1* commented code : override shipping method validation
	// - *2* : overriding Quote_Payment object to disable payment method validation
	// - *3* setting custom payment info
    public function setCustomPaymentMethod($quoteId, $paymentData, $paymentMethod, $orderNumber, $store=null)
    {
        $quote = $this->_getQuote($quoteId, $store);
        $store = $quote->getStoreId();

        $paymentData = $this->_preparePaymentData($paymentData);

        if (empty($paymentData)) {
            $this->_fault("payment_method_empty");
        }

        if ($quote->isVirtual()) {
            // check if billing address is set
            if (is_null($quote->getBillingAddress()->getId()) ) {
                $this->_fault('billing_address_is_not_set');
            }
            $quote->getBillingAddress()->setPaymentMethod(isset($paymentData['method']) ? $paymentData['method'] : null);
        } else {
            // check if shipping address is set
            if (is_null($quote->getShippingAddress()->getId()) ) {
                $this->_fault('shipping_address_is_not_set');
            }
            $quote->getShippingAddress()->setPaymentMethod(isset($paymentData['method']) ? $paymentData['method'] : null);
        }

        if (!$quote->isVirtual() && $quote->getShippingAddress()) {
            $quote->getShippingAddress()->setCollectShippingRates(true);
        }

        $total = $quote->getBaseSubtotal();

		// *1*
//        $methods = Mage::helper('payment')->getStoreMethods($store, $quote);
//        foreach ($methods as $key=>$method) {
//            if ($method->getCode() == $paymentData['method']) {
//                /** @var $method Mage_Payment_Model_Method_Abstract */
//                if (!($this->_canUsePaymentMethod($method, $quote)
//                        && ($total != 0
//                            || $method->getCode() == 'free'
//                            || ($quote->hasRecurringItems() && $method->canManageRecurringProfiles())))) {
//                    $this->_fault("method_not_allowed");
//                }
//            }
//        }
		// END OF *1*

        try {

			 // *2* Mage_Sales_Model_Quote_Payment overrides
            // $payment = $quote->getPayment();
			$payment = new GoDataFeed_Services_Model_Quote_Payment();
			$quote->addPayment($payment);
			// END OF *2*

            $payment->importData($paymentData);

			// *3*
			$paymentInfo = $payment->getMethodInstance()->getInfoInstance();
			$paymentInfo->setCcOwner($quote->getBillingAddress()->getFirstname() . ' ' . $quote->getBillingAddress()->getLastname());
			$paymentInfo->setCcType($paymentMethod);
			$paymentInfo->setCcSsIssue($orderNumber);
			// end of modifications *3*

            $quote->setTotalsCollectedFlag(false)
                    ->collectTotals()
                    ->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('payment_method_is_not_set', $e->getMessage());
        }
        return true;
    }

}
