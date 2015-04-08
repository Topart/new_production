<?php
/**
 * Created by PhpStorm.
 * User: diegopalda
 * Date: 08/04/15
 * Time: 10:11 AM
 */
class Onetree_ShopByArtist_Helper_Data extends Mage_Core_Helper_Abstract {

	const XML_PATH_CONFIG_CATEGORY_ID = 'shopbyartist/shopbyartist_config/category_id';
	
	public function getArtistCategoryId(){
		return Mage::getStoreConfig(self::XML_PATH_CONFIG_CATEGORY_ID);
	}
}