<?php
/**
 * Created by PhpStorm.
 * User: diegopalda
 * Date: 19/05/15
 * Time: 03:29 PM
 */
class Onetree_RelatedProducts_Block_List extends Mage_Core_Block_Template
{
   private $total_items;


   public function _construct(){
       $this->total_items = Mage::helper('onetree_relatedproducts/data')->getTotalItems();
       parent::_construct();
   }

    public function getProductsSameCategory(){
        if(Mage::registry('product')){
            $categories = Mage::registry('product')->getCategoryCollection()->load();
            $category =  $categories->getFirstItem();
            $total_products = $category->getProductCount();
            $total_pages = floor($total_products / $this->total_items);
            $products = $categories->getFirstItem()->getProductCollection()->setPageSize($this->total_items)->setCurPage(rand(1,$total_pages));
        }
        return $products;
    }

    public function getProductsSameArtist(){
        if(Mage::registry('product')){
            $categories = Mage::registry('product')->getCategoryCollection()->load();
            $category = $categories->getItemsByColumnValue('parent_id',1580);
            if(!empty($category)){
                $category = $category[0];
                $total_products = $category->getProductCount();
                $total_pages = floor($total_products / $this->total_items);
                $products = $category->getProductCollection()->setPageSize($this->total_items)->setCurPage(rand(1,$total_pages));;
            }
        }
        return $products;
    }

    public function showProductsSameCategory(){
        return Mage::helper('onetree_relatedproducts/data')->showProductsSameCategory();
    }

    public function showProductsSameArtist(){
        return Mage::helper('onetree_relatedproducts/data')->showProductsSameArtist();
    }

    public function getAutoPlay(){
        $time = Mage::helper('onetree_relatedproducts/data')->getAutoPlay();
        return ($time == 0) ? 'false' : $time ;
    }

    public function getItemsPerPage(){
        return Mage::helper('onetree_relatedproducts/data')->getItemsPerPage();
    }

    public function getItemsPerPageMobile(){
        return Mage::helper('onetree_relatedproducts/data')->getItemsPerPageMobile();
    }

    public function getImageWidth()
    {
       return  Mage::helper('onetree_relatedproducts/data')->getImageWidth();

    }

    public function getImageHeight()
    {
       return Mage::helper('onetree_relatedproducts/data')->getImageHeight();

    }
}
