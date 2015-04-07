<?php

class Springbot_Combine_Helper_Parser extends Mage_Core_Helper_Abstract
{
	protected $_transEmails;

	public function getParentSkus($entityId)
	{
		$parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($entityId);

		$parentCollection = Mage::getResourceModel('catalog/product_collection')
			->addFieldToFilter('entity_id', array('in' => $parentIds))
			->addAttributeToSelect('sku');
		$parentSkusArray = $parentCollection->getColumnValues('sku');
		$parentIdsArray = $parentCollection->getColumnValues('entity_id');

		foreach ($parentSkusArray as $index => $sku) {
			if (!$sku) {
				$parentSkusArray[$index] = Springbot_Boss::NO_SKU_PREFIX . $parentIdsArray[$index];
			}
		}

		return $parentSkusArray;
	}

	/**
	 * Get top level sku
	 *
	 * This aims to get the top level sku.  The getSku method for the product
	 * model is overloaded providing the type instance version of the sku
	 * meaning that it gives the simple sku for configurable or grouped products
	 * we need to get the _data array directly and pass that sku up to ensure the
	 * parent sku.
	 *
	 * @param $product
	 * @return string
	 */
	public function getTopLevelSku($product)
	{
		if($product instanceof Mage_Catalog_Model_Product) {
			$data = $product->getData();
			if (isset($data['sku']) && $data['sku']) {
				return $data['sku'];
			}
			else {
				return $this->_getSkuFailsafe($product);
			}
		}
	}

	protected function _getSkuFailsafe($product)
	{
		if ($sku = $product->getSku()) {
			return $sku;
		}
		else {
			return Springbot_Boss::NO_SKU_PREFIX . $product->getEntityId();
		}
	}

	/**
	 * Gets accessible sku, product is visible from frontend
	 *
	 * @param Mage_Sales_Model_Order_Item
	 * @return string
	 */
	public function getAccessibleSkuFromSalesItem($item)
	{
		$product = Mage::getModel('catalog/product')->load($item->getProductId());
		if($product) {
			if(!$this->isAccessible($product)) {
				Springbot_Log::debug('Product not visible - attempt to find parent');
				$product = $this->getParentProductFromSalesItem($item);
			}
			return $this->getTopLevelSku($product);
		}
	}

	public function isAccessible(Mage_Catalog_Model_Product $product)
	{
		return
			!(
				$product->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE ||
				$product->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_DISABLED
			);
	}

	/**
	 * Uses config data to get parent product for purchased simple
	 *
	 * @param Mage_Sales_Model_Order_Item
	 * @return Mage_Catalog_Model_Product
	 */
	public function getParentProductFromSalesItem($item)
	{
		$values = $item->getBuyRequest()->toArray();

		if($type = $item->getOptionsByCode('product_type')) {
			if($parentProductId = $type->getProductId()) {
				$product = Mage::getModel('catalog/product')->load($parentProductId);
			}
		}
		else if($item->hasParentItemId()) {
			$product = $item->getParentItem()->getProduct();
		}
		else if (isset($values['super_product_config']) && ($values['super_product_config']['product_type'] == 'grouped')) {
			$parentProductId = $values['super_product_config']['product_id'];
			$product = Mage::getModel('catalog/product')->load($parentProductId);
		}
		else {
			$product = $item->getProduct();
		}
		return $product;
	}

	public function getChildProductIds($product)
	{
		$ids = Mage::getModel('catalog/product_type_configurable')->getChildrenIds($product->getId());
		if(isset($ids[0]) && is_array($ids[0])) {
			$ids = $ids[0];
		}
		return $ids;
	}

	public function getCustomAttributeNames($product)
	{
		return array_keys($this->getCustomAttributes($product));
	}

	public function getCustomAttributes($product, $len = -1)
	{
		$return = array();
		$helper = Mage::helper('combine/attributes');
		$attributes = $helper->getCustomAttributesBySet($product->getAttributeSetId());

		foreach($attributes as $attribute) {
			$code = $attribute->getAttributeCode();

			if($attribute->usesSource()) {
				try {
					$value = $this->_getAttributeText($product, $code);
				} catch (Mage_Eav $e) {
					Springbot_Log::debug(print_r($e->getMessage(), true));
				}
			} else {
				$value = $product->getData($code);
			}

			$return[$code] = $len > 0 ? substr($value, 0, $len) : $value;
		}

		return $return;
	}

	private function _getAttributeText($product, $attributeCode) {
		$resource = $product->getResource();
		if (is_object($resource)) {
			$attribute = $resource->getAttribute($attributeCode);
			if (is_object($attribute)) {
				if(Mage::getModel($attribute->getSourceModel())) {
					$source = $attribute->getSource();
					if (is_object($source)) {
						return $source->getOptionText($product->getData($attributeCode));
					}
				}
			}
		}
		return null;
	}

	public function hasImage($product)
	{
		if($product instanceof Mage_Catalog_Model_Product) {
			if(($image = $product->getImage()) && $this->_exists($image) ) {
				return true;
			} else if(($image = $product->getSmallImage()) && $this->_exists($image) ) {
				return true;
			} else if(($image = $product->getThumbnail()) && $this->_exists($image) ) {
				return true;
			} else if($product instanceof Mage_Catalog_Model_Product) {
				if($gallery = $product->getMediaGalleryImages()) {
					return $gallery->count() > 0;
				}
			}
		}
		return false;
	}

	public function getLandingUrl($product)
	{
		if($product instanceof Mage_Catalog_Model_Product) {
			$linkType = Mage::getStoreConfig('springbot/advanced/product_url_type');
			if ($linkType == 'id_path') {
				$url = Mage::getUrl('catalog/product/view', array(
					'id' => $product->getId(),
					'_store' => $product->getStoreId(),
				));
			} else if ($linkType == 'in_store') {
				$url = $product->getUrlInStore();
			} else if ($uri = $product->getUrlPath() && $linkType == 'default') {
				$url = Mage::helper('combine/harvest')->getStoreUrl($product->getStoreId()) . '/' . $product->getUrlPath();
			} else {
				$url = $product->getProductUrl(false);
			}
			// remove calling script from url (Mage logic ftw)
			$url = preg_replace('/\/springbot.php\//', '/', $url);
		}
		return $url;
	}

	public function getImageUrl($product)
	{
		if($product instanceof Mage_Catalog_Model_Product) {
			if((Mage::getStoreConfig('springbot/images/use_cached_images'))) {
				$img = Mage::helper('catalog/image')->init($product, 'image');
				if($size = Mage::getStoreConfig('springbot/images/pixel_width')) {
					$img->resize($size);
				}
				return (string) $img;
			}
			else if(($image = $product->getImage()) && $this->_exists($image) ) {
				// main
			}
			else if(($image = $product->getSmallImage()) && $this->_exists($image) ) {
				// small
			}
			else if(($image = $product->getThumbnail()) && $this->_exists($image) ) {
				// thumbnail
			}
			else if ($product->getMediaGalleryImages() && $product->getMediaGalleryImages()->getSize() > 0) {
				// First item from gallery
				return $product->getMediaGalleryImages()->getFirstItem()->getUrl();
			}
			else {
				// if we get here, the image doesn't exist, return null
				return null;
			}

			if(strpos($image, DS) !== 0) {
				$image = DS . $image;
			}
			return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $image;
		}
	}

	public function isTransactionalEmail($email)
	{
		return array_search($email, $this->_getTransEmails()) !== false;
	}

	protected function _exists($file, $type = Mage_Core_Model_Store::URL_TYPE_MEDIA)
	{
		$file = Mage::getBaseDir($type) . '/catalog/product/' . $file;
		return file_exists($file);
	}

	protected function _getTransEmails()
	{
		if(!isset($this->_transEmails)) {
			foreach(Mage::getStoreConfig('trans_email') as $k => $v) {
				if(isset($v['email'])) { $this->_transEmails[] = $v['email']; }
			}
		}
		return $this->_transEmails;
	}

}
