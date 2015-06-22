<?php
class GoDataFeed_Services_Model_Quote_Payment extends Mage_Sales_Model_Quote_Payment
{
	// MAGENTO 1.6.2 (Mage_Sales_Model_Quote_Payment)
	// Modifications :
	// - *1* commented code
	public function importData(array $data)
	{
		$data = new Varien_Object($data);
		Mage::dispatchEvent(
			$this->_eventPrefix . '_import_data_before',
			array(
				$this->_eventObject => $this,
				'input' => $data,
			)
		);

		$this->setMethod($data->getMethod());
		$method = $this->getMethodInstance();
		$method->setStore($this->getQuote()->getStore());

		/**
		 * Payment avalability related with quote totals.
		 * We have recollect quote totals before checking
		 */
		$this->getQuote()->collectTotals();

		// *1* COMMENTED VALIDATION CODE
		//		if (!$method->isAvailable($this->getQuote())) {
		//			Mage::throwException(Mage::helper('sales')->__('Requested Payment Method is not available'));
		//		}
		// END OF *1*

		$method->assignData($data);
		/*
				 * validating the payment data
				 */
		$method->validate();
		return $this;
	}

	// MAGENTO 1.6.2 (Mage_Sales_Model_Quote_Payment)
	// Modifications :
	// - full overrides to allow the custom OrderSync payment method to be returned
	public function getMethodInstance()
	{
		$oderSyncPaymentMethod = new GoDataFeed_Services_Model_Method_OrderSync();
		$oderSyncPaymentMethod->setInfoInstance($this);
		$this->setMethodInstance($oderSyncPaymentMethod);
		return $oderSyncPaymentMethod;
	}
}
