<?php
// MAGENTO 1.6.2 (Mage_Checkout_Model_Cart_Product_Api)
// Modifications :
// - Cart_Product_Api not present in mage 1.4.0.1
// - extends
// - customAdd method name
class GoDataFeed_Services_Model_Cart_Product_Api extends GoDataFeed_Services_Model_Api_Resource_Checkout_Product
{
    protected function _prepareProductsData($data)
    {
        if (!is_array($data)) {
            return null;
        }

        $_data = array();
        if (is_array($data) && is_null($data[0])) {
            $_data[] = $data;
        } else {
            $_data = $data;
        }

        return $_data;
    }

	// MAGENTO 1.6.2 (Mage_Checkout_Model_Cart_Product_Api)
	// Modifications :
	// - customAdd method name
	// - *1* verifies that the product has been found
    public function customAdd($quoteId, $productsData, $store=null)
    {
        $quote = $this->_getQuote($quoteId, $store);
        if (empty($store)) {
            $store = $quote->getStoreId();
        }

        $productsData = $this->_prepareProductsData($productsData);
        if (empty($productsData)) {
            $this->_fault('invalid_product_data');
        }

        $errors = array();
        foreach ($productsData as $productItem) {
            if (isset($productItem['product_id'])) {
                $productByItem = $this->_getProduct($productItem['product_id'], $store, "id");
            } else if (isset($productItem['sku'])) {
                $productByItem = $this->_getProduct($productItem['sku'], $store, "sku");
            } else {
                $errors[] = Mage::helper('checkout')->__("One item of products do not have identifier or sku");
                continue;
            }

			// *1*
			if($productByItem->getId() == null) {

				$errorMessage = "requested product could not be found. ";
				if (isset($productItem['product_id'])) {
					$errorMessage .= "product id = " . $productItem['product_id'] . ". ";
				}
				if (isset($productItem['sku'])) {
					$errorMessage .= "product sku = " . $productItem['sku'] . ". ";
				}

				Mage::throwException($errorMessage);
			}
			// END OF *1*

            $productRequest = $this->_getProductRequest($productItem);
            try {
                $result = $quote->addProduct($productByItem, $productRequest);
                if (is_string($result)) {
                    Mage::throwException($result);
                }
            } catch (Mage_Core_Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            $this->_fault("add_product_fault", implode(PHP_EOL, $errors));
        }

        try {
            $quote->collectTotals()->save();
        } catch(Exception $e) {
            $this->_fault("add_product_quote_save_fault", $e->getMessage());
        }

        return true;
    }
}
