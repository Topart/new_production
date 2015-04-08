<?php

class Onetree_ShopbyArtist_Block_List extends Mage_Core_Block_Template{

    
    
    public  function getList(){
        $id = Mage::helper('onetree_shopbyartist')->getArtistCategoryId();
        $artists = Mage::getModel('catalog/category')->load($id)->getChildrenCategories();
        $list = array();
        foreach($artists as $artist){
            if($artist->getProductCount() > 0 ) {
                $name = $artist->getName();
                $list[$name[0]][] = $artist;
            }
        }
        return $list;
    }
}