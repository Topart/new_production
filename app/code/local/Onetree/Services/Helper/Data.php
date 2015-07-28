<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 7/23/15
 * Time: 11:19
 */ 
class Onetree_Services_Helper_Data extends Mage_Core_Helper_Abstract {

    public function getBasePrice($product) {
        $key = 'Size';
        $productIds[] = $product->getId();
        $collection = Mage::getModel('catalog/product_option')->getCollection()
            ->addFieldToFilter('product_id', array('in' =>$productIds))
            ->addTitleToResult(Mage::app()->getStore()->getStoreId())
            ->addPriceToResult(Mage::app()->getStore()->getStoreId())
            ->setOrder('sort_order', 'asc')
            ->setOrder('title', 'asc');
        $collection->getSelect()->where(('IF(store_option_title.title IS NULL, default_option_title.title, store_option_title.title) ="'.$key.'"'));
        $collection->addValuesToResult(Mage::app()->getStore()->getStoreId());

        $option = $collection->getItemByColumnValue('product_id',$product->getId());
        $cheapestPrice = $product->getPrice();

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
                $cheapestPrice = $aPrices[0];
            }
        }
        return $cheapestPrice;
    }
}