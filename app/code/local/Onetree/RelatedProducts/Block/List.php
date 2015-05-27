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
    protected $_productsSameCategory = null;
    protected $_productsSameArtist = null;
    protected $_categories = null;
    protected $_category = null;
    protected $_artist = null ;
    protected $_product = null;

   public function _construct(){
       $this->total_items = Mage::helper('onetree_relatedproducts/data')->getTotalItems();
       parent::_construct();
   }

    public function getProductsSameCategory(){
        $products = null;
        if(Mage::registry('product')){
            $category =  Mage::registry('current_category');
            $total_products = $category->getProductCount();
            $total_pages = floor($total_products / $this->total_items);
            $products = $category->getProductCollection()->setPageSize($this->total_items)->setCurPage(rand(1,$total_pages));
            $this->_category = $category;
        }
        return $products;
    }

    public function getProductsSameArtist(){

        if(is_null($this->_productsSameArtist)){
            $categories = $this->_getCategories();
            $category = $categories->getItemByColumnValue('parent_id',$this->getArtistCategoryId());
            if($category instanceof Mage_Catalog_Model_Category && $category->getId()){
                $total_products = $category->getProductCount();
                $total_pages = floor($total_products / $this->total_items);
                $this->_productsSameArtist = $category->getProductCollection()->addAttributeToFilter('entity_id', array('neq' => $this->_product->getId()))->setPageSize($this->total_items)->setCurPage(rand(1,$total_pages));
                $this->_artist = $category;
            }

        }

        return $this->_productsSameArtist;
    }

    protected function _getCategories(){

        if(is_null($this->_categories)){
            $this->_categories = Mage::registry('product')->getCategoryCollection()->addAttributeToSelect('name')->load();
            $this->_product = Mage::registry('product');
        }
        return $this->_categories;
    }

    public function getCategory(){
        return $this->_category;
    }

    public function getArtist() {
        if(is_null($this->_artist)){
            $categories = $this->_getCategories();
            $this->_artist = $categories->getItemByColumnValue('parent_id',$this->getArtistCategoryId());
        }
        return $this->_artist;
    }

    public function showProductsSameCategory(){
        return Mage::helper('onetree_relatedproducts')->showProductsSameCategory();
    }

    public function showProductsSameArtist(){
        return Mage::helper('onetree_relatedproducts')->showProductsSameArtist();
    }

    public function getAutoPlay(){
        $time = Mage::helper('onetree_relatedproducts')->getAutoPlay();
        return ($time == 0) ? 'false' : $time ;
    }

    public function getItemsPerPage(){
        return Mage::helper('onetree_relatedproducts')->getItemsPerPage();
    }

    public function getItemsPerPageMobile(){
        return Mage::helper('onetree_relatedproducts')->getItemsPerPageMobile();
    }

    public function getImageWidth()
    {
       return  Mage::helper('onetree_relatedproducts')->getImageWidth();

    }

    public function getImageHeight()
    {
       return Mage::helper('onetree_relatedproducts')->getImageHeight();

    }

    public function getArtistCategoryId(){
        return Mage::helper('onetree_relatedproducts')->getArtistCategoryId();
    }

    public function getCurrentCategory(){
        return Mage::registry('current_category');
    }


}
