<?php
class GoDataFeed_Services_Model_Order_Payment extends Mage_Sales_Model_Order_Payment
{
	// MAGENTO 1.6.2 (Mage_Sales_Model_Order_Payment)
	// Modifications :
	// - full overrides to allow the custom OrderSync payment method to be returned
	public function getMethodInstance()
	{
		$orderSyncPaymentMethod = new GoDataFeed_Services_Model_Method_OrderSync();
		$orderSyncPaymentMethod->setInfoInstance($this);
		$this->setMethodInstance($orderSyncPaymentMethod);
		return $orderSyncPaymentMethod;
	}
}
