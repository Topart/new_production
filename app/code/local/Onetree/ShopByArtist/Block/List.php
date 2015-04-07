<?php
/**
 * Created by PhpStorm.
 * User: diegopalda
 * Date: 02/04/15
 * Time: 02:35 PM
 */

class Onetree_ShopbyArtist_Block_List extends Mage_Core_Block_Template{


    public  function getList(){
        $category = Mage::getModel('catalog/category')->getCollection()
            ->addAttributeToSelect('name')
            ->addAttributeToFilter('name',array('eq' => 'Artists'))->getFirstItem();
        $chilCategories = Mage::getModel('catalog/category')->getCollection()
            ->addAttributeToSelect('name')
            ->setLoadProductCount(true)
            ->addIdFilter($category->getChildren());

        $list = array();

        foreach($chilCategories as $artist){
            if($artist->getProductCount() > 0 ) {
                $name = $artist->getName();
                $list[$name[0]][] = $artist;
            }
        }

        return $list;
    }
}