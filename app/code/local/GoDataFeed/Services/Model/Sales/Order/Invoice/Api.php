<?php
// DUPLICATED CODE FROM MAGENTO (Mage_Sales_Model_Order_Invoice_Api)
// CODE VERSION : 1.4.0.1
// Modifications :
// - cancel
class GoDataFeed_Services_Model_Sales_Order_Invoice_Api extends Mage_Sales_Model_Order_Invoice_Api
{
	// MAGENTO 1.4.0.1 (Mage_Sales_Model_Order_Invoice_Api)
	// Modifications :
	// - *1* commented code : override cancellation validation
    public function customCancel($invoiceIncrementId)
    {
        $invoice = Mage::getModel('sales/order_invoice')->loadByIncrementId($invoiceIncrementId);

        /* @var $invoice Mage_Sales_Model_Order_Invoice */

        if (!$invoice->getId()) {
            $this->_fault('not_exists');
        }

//		*1*
//        if (!$invoice->canCancel()) {
//            $this->_fault('status_not_changed', Mage::helper('sales')->__('Invoice can not be canceled'));
//        }
// 		END OF *1*

        try {
            $invoice->cancel();
            $invoice->getOrder()->setIsInProcess(true);
            $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder())
                ->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('status_not_changed', $e->getMessage());
        } catch (Exception $e) {
            $this->_fault('status_not_changed', Mage::helper('sales')->__('Invoice cancel problem'));
        }

        return true;
    }
}
