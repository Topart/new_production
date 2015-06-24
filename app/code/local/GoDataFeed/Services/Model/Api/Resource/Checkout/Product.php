<?php
// MAGENTO 1.6.2 (Mage_Checkout_Model_Api_Resource_Product)
// Modifications :
// - extends
// - _getProduct
class GoDataFeed_Services_Model_Api_Resource_Checkout_Product extends GoDataFeed_Services_Model_Api_Resource_Checkout
{
    /**
     * Default ignored attribute codes
     *
     * @var array
     */
    protected $_ignoredAttributeCodes = array('entity_id', 'attribute_set_id', 'entity_type_id');

	// MAGENTO 1.6.2
	// Modifications :
	// - code taken from Mage_Catalog_Helper_Product which lacks the getProduct method in 1.4
	// - *1* bug fix when using SKU instead of productId
	protected function _getProduct($productId, $store = null, $identifierType = null)
	{
		//        $product = Mage::helper('catalog/product')->getProduct($productId,
		//                        $this->_getStoreId($store),
		//                        $identifierType
		//        );
		//        return $product;

		$loadByIdOnFalse = false;
		if ($identifierType == null) {
			if (is_string($productId) && !preg_match("/^[+-]?[1-9][0-9]*$|^0$/", $productId)) {
				$identifierType = 'sku';
				$loadByIdOnFalse = true;
			} else {
				$identifierType = 'id';
			}
		}

		/** @var $product Mage_Catalog_Model_Product */
		$product = Mage::getModel('catalog/product');
		if ($store !== null) {
			$product->setStoreId($store);
		}
		if ($identifierType == 'sku') {
			$idBySku = $product->getIdBySku($productId);
			if ($idBySku) {
				$productId = $idBySku;
				$loadByIdOnFalse = true; // *1* http://www.magentocommerce.com/boards/viewthread/245129
			}
			if ($loadByIdOnFalse) {
				$identifierType = 'id';
			}
		}

		if ($identifierType == 'id' && is_numeric($productId)) {
			$productId = !is_float($productId) ? (int)$productId : 0;
			$product->load($productId);
		}

		return $product;

	}

    /**
     * Get request for product add to cart procedure
     *
     * @param   mixed $requestInfo
     * @return  Varien_Object
     */
    protected function _getProductRequest($requestInfo)
    {
        if ($requestInfo instanceof Varien_Object) {
            $request = $requestInfo;
        } elseif (is_numeric($requestInfo)) {
            $request = new Varien_Object();
            $request->setQty($requestInfo);
        } else {
            $request = new Varien_Object($requestInfo);
        }

        if (!$request->hasQty()) {
            $request->setQty(1);
        }
        return $request;
    }
//
//    /**
//     * Get QuoteItem by Product and request info
//     *
//     * @param Mage_Sales_Model_Quote $quote
//     * @param Mage_Catalog_Model_Product $product
//     * @param Varien_Object $requestInfo
//     * @return Mage_Sales_Model_Quote_Item
//     * @throw Mage_Core_Exception
//     */
//    protected function _getQuoteItemByProduct(Mage_Sales_Model_Quote $quote,
//                            Mage_Catalog_Model_Product $product,
//                            Varien_Object $requestInfo)
//    {
//        $cartCandidates = $product->getTypeInstance(true)
//                        ->prepareForCartAdvanced($requestInfo,
//                                $product,
//                                Mage_Catalog_Model_Product_Type_Abstract::PROCESS_MODE_FULL
//        );
//
//        /**
//         * Error message
//         */
//        if (is_string($cartCandidates)) {
//            throw Mage::throwException($cartCandidates);
//        }
//
//        /**
//         * If prepare process return one object
//         */
//        if (!is_array($cartCandidates)) {
//            $cartCandidates = array($cartCandidates);
//        }
//
//        /** @var $item Mage_Sales_Model_Quote_Item */
//        $item = null;
//        foreach ($cartCandidates as $candidate) {
//            if ($candidate->getParentProductId()) {
//                continue;
//            }
//
//            $item = $quote->getItemByProduct($candidate);
//        }
//
//        if (is_null($item)) {
//            $item = Mage::getModel("sales/quote_item");
//        }
//
//        return $item;
//    }
}
