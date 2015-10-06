<?php

class Onetree_Catalog_Block_Product_List extends Mage_Catalog_Block_Product_List
{
    protected $_getProductOptionsCollection;
    protected $_key = 'Size';
    protected function _getProductOptionsCollection(){

        if(is_null($this->_getProductOptionsCollection)){
            $loadProductCollection = $this->_getProductCollection();

            $productIds = array();
            foreach($loadProductCollection as $product){
                $productIds[] = $product->getId();
            }
            $collection = Mage::getModel('catalog/product_option')->getCollection()
                ->addFieldToFilter('product_id', array('in' =>$productIds))
                ->addTitleToResult(Mage::app()->getStore()->getStoreId())
                ->addPriceToResult(Mage::app()->getStore()->getStoreId())
                ->setOrder('sort_order', 'asc')
                ->setOrder('title', 'asc');
            $collection->getSelect()->where(('IF(store_option_title.title IS NULL, default_option_title.title, store_option_title.title) ="'.$this->_key.'"'));
            $collection->addValuesToResult(Mage::app()->getStore()->getStoreId());
            $this->_getProductOptionsCollection = $collection;
        }
        return $this->_getProductOptionsCollection;

    }

    public function getProductOptionsCollection(){
        return $this->_getProductOptionsCollection();
    }

    public function getTopArtPrice($product) {
        $loadProductOptionCollection = $this->_getProductOptionsCollection();
        $option = $loadProductOptionCollection->getItemByColumnValue('product_id', $product->getId());
        $cheapestPrice = $this->getPriceHtml($product, true);

        if (!is_null($option)) {
            $optionValues = $option->getValues();
            $aPrices = array();

            foreach ($optionValues as $value) {
                if ($value->getPrice() > 0) {
                    $aPrices[] = $value->getPrice();
                }
            }
            
            if (count($aPrices)) {
                sort($aPrices);
                $cheapestPrice = $this->helper('core')->formatCurrency($aPrices[0]);
            }
        }

        return $this->__("from") . ' ' . $cheapestPrice;
    }

}
