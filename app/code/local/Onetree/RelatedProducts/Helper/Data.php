<?php
/**
 * Created by PhpStorm.
 * User: diegopalda
 * Date: 19/05/15
 * Time: 03:27 PM
 */ 
class Onetree_RelatedProducts_Helper_Data extends Mage_Core_Helper_Abstract {

    const XML_PATH_SHOW_SAME_CATEGORY = 'related_products/related_products_same_cateogry/show_same_category';
    const XML_PATH_TOTAL_ITEMS = 'related_products/related_products_global_config/total_items';
    const XML_PATH_SHOW_SAME_ARTIST = 'related_products/related_products_same_artist/show_same_artists';
    const XML_PATH_ARTIST_CATEGORY_ID = 'related_products/related_products_same_artist/category_id';
    const XML_PATH_AUTOPLAY = 'related_products/related_products_global_config/autoplay';
    const XML_PATH_ITEMS_PER_PAGE = 'related_products/related_products_global_config/items_per_page';
    const XML_PATH_ITEMS_PER_PAGE_MOBILE = 'related_products/related_products_global_config/items_mobile';
    const XML_PATH_IMAGE_SIZE = 'related_products/related_products_global_config/image_size';

    public function showProductsSameCategory(){
        return Mage::getStoreConfig(self::XML_PATH_SHOW_SAME_CATEGORY);
    }

    public function showProductsSameArtist(){
        return Mage::getStoreConfig(self::XML_PATH_SHOW_SAME_ARTIST);
    }

    public function getTotalItems() {
        return Mage::getStoreConfig(self::XML_PATH_TOTAL_ITEMS);
    }

    public function getArtistCategoryId(){
        return Mage::getStoreConfig(self::XML_PATH_ARTIST_CATEGORY_ID);
    }

    public function getAutoPlay(){
        return Mage::getStoreConfig(self::XML_PATH_AUTOPLAY);
    }

    public function getItemsPerPage() {
        return Mage::getStoreConfig(self::XML_PATH_ITEMS_PER_PAGE);
    }

    public function getItemsPerPageMobile(){
        return Mage::getStoreConfig(self::XML_PATH_ITEMS_PER_PAGE_MOBILE);
    }

    public function getImageSize()
    {
        $width  = 150;
        $height = 150;
        $size   = Mage::getStoreConfig(self::XML_PATH_IMAGE_SIZE);
        $size   = explode('x', $size);
        if (isset($size[0]) && intval($size[0]) > 0) {
            $width = intval($size[0]);
        }

        if (isset($size[1]) && intval($size[1]) > 0) {
            $height = intval($size[1]);
        }

        return array($width, $height);
    }

    public function getImageWidth()
    {
        $size = $this->getImageSize();

        return $size[0];
    }

    public function getImageHeight()
    {
        $size = $this->getImageSize();

        return $size[1];
    }

}