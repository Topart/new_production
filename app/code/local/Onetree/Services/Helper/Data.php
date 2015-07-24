<?php
/**
 * Created by PhpStorm.
 * User: David
 * Date: 7/23/15
 * Time: 11:19
 */ 
class Onetree_Services_Helper_Data extends Mage_Core_Helper_Abstract {

    public function getBasePrice($product) {

        $myblock = Mage::app()->getLayout()->createBlock('catalog/product_list')->getPriceBase($product);
        return preg_replace("/<[^>]*>/","",$myblock);

    }

}