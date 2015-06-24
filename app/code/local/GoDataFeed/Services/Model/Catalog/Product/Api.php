<?php

class GoDataFeed_Services_Model_Catalog_Product_Api extends Mage_Catalog_Model_Product_Api
{
	const STOCK_ITEM_MODEL = 'cataloginventory/stock_item';
	const ATTRIBUTE_SET_MODEL = 'eav/entity_attribute_set';
	const CATALOG_PRODUCT_MODEL = 'catalog/product';
	const CATALOG_CATEGORY_MODEL = 'catalog/category';
	const CONFIGURABLE_PRODUCT_MODEL = "catalog/product_type_configurable";
	const GROUPED_PRODUCT_MODEL = "catalog/product_type_grouped";

	const PRODUCT_NAME_FIELD = 'name';
	const DESCRIPTION_FIELD = 'description';
	const SHORT_DESCRIPTION_FIELD = 'short_description';
	const CATEGORY_NAME_FIELD = 'name';
	const MANUFACTURER_FIELD = 'manufacturer';
	const VISIBILITY_FIELD = 'visibility';

	const CATEGORY_SEPARATOR = ' > ';

	public function count($filters, $stockQuantityFilterAmount, $store, $responseField)
	{
		$filteredProductsCollection = $this->getProductsFilteredByStockQuantity(
			$filters,
			$stockQuantityFilterAmount,
			$store,
			array(),
			array(),
			false,
			false,
			false,
			0,
			0
		);

		$numberOfProducts = 0;
		if(!empty($filteredProductsCollection)) {
			$numberOfProducts = $filteredProductsCollection->getSize();
		}

		return array($responseField => $numberOfProducts);
    }

	public function extendedList(
		$filters,
		$stockQuantityFilterAmount,
		$store,
		$attributes,
		$customAttributes,
		$qtyConfig,
		$isInStockConfig,
		$attributeSetNameConfig,
		$categoryBreadCrumbConfig,
		$manufacturerNameConfig,
		$absoluteUrlConfig,
		$absoluteImageUrlConfig,
		$scrubProductName,
		$scrubDescription,
		$scrubShortDescription,
		$scrubAttributeSetName,
		$scrubCustomAttribute,
		$pageNumber,
		$productsPerPage,
		$parentSKUConfig,
        $additionalImagesConfig
    )
    {

		if(empty($store)) {
			$baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
			$imageBaseURL = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)."catalog/product";
		} else {
			$baseUrl = Mage::getModel('core/store')->load($store)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
			$imageBaseURL = Mage::getModel('core/store')->load($store)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA)."catalog/product";
		}

		$resultItems = array();

		$storeId = $this->_getStoreId($store);

		// GET PRODUCTS FOR REQUESTED STORE WITH SPECIFIED FILTERS
		$manufacturerNameRequested = $manufacturerNameConfig[0];
		$absoluteUrlRequested = $absoluteUrlConfig[0];
		$absoluteImageUrlRequested = $absoluteImageUrlConfig[0];
		$filteredProductsCollection =
				$this->getProductsFilteredByStockQuantity(
					$filters,
					$stockQuantityFilterAmount,
					$store,
					$attributes,
					$customAttributes,
					$manufacturerNameRequested,
					$absoluteUrlRequested,
					$absoluteImageUrlRequested,
					$pageNumber,
					$productsPerPage
				);

		if(!empty($filteredProductsCollection)) {

			foreach ($filteredProductsCollection as $productToRetrieve) {

				$resultItem = array();
				$productIdToRetrieve = $productToRetrieve->getId();

				// STANDARD ATTRIBUTES
				if(!empty($attributes) && is_array($attributes)) {
					foreach($attributes as $attribute)
					{
						$resultItem[$attribute] = $productToRetrieve->getData($attribute);
					}
				}

				// CUSTOM ATTRIBUTES
				if(!empty($customAttributes) && is_array($customAttributes)) {
					foreach($customAttributes as $customAttribute)
					{
						$attributeField = $productToRetrieve->getResource()->getAttribute($customAttribute);

						//If it's an option or multiselect attribute
						if(!empty($attributeField) && $attributeField->usesSource() && $productToRetrieve->getAttributeText($customAttribute)) {
							$attributeFieldValue = $productToRetrieve->getAttributeText($customAttribute);
						}
						else {
							$attributeFieldValue = $productToRetrieve->getData($customAttribute);
						}

						if($scrubCustomAttribute) {
							$attributeFieldValue = $this->scrubData($attributeFieldValue);
						}

						$resultItem[$customAttribute] = $attributeFieldValue;
					}
				}

				// PRODUCT NAME SCRUBBING
				if(in_array(self::PRODUCT_NAME_FIELD, $attributes) && $scrubProductName) {
					$productName = $resultItem[self::PRODUCT_NAME_FIELD];
					$resultItem[self::PRODUCT_NAME_FIELD] = $this->scrubData($productName);
				}

				// DESCRIPTION SCRUBBING
				if(in_array(self::DESCRIPTION_FIELD, $attributes) && $scrubDescription) {
					$resultItem[self::DESCRIPTION_FIELD] =
							$this->scrubData($resultItem[self::DESCRIPTION_FIELD]);
				}

				// SHORT DESCRIPTION SCRUBBING
				if(in_array(self::SHORT_DESCRIPTION_FIELD, $attributes) && $scrubShortDescription) {
					$resultItem[self::SHORT_DESCRIPTION_FIELD] =
							$this->scrubData($resultItem[self::SHORT_DESCRIPTION_FIELD]);
				}

				// IS IN STOCK & QUANTITY ATTRIBUTES
				$stockQuantityRequested = $qtyConfig[0];
				$stockStatusRequested = $isInStockConfig[0];
				if($stockQuantityRequested || $stockStatusRequested) {

					$inventoryStatus = Mage::getModel(self::STOCK_ITEM_MODEL)->loadByProduct($productToRetrieve);

					if (!empty($inventoryStatus)) {

						if($stockQuantityRequested) {
							$responseField = $qtyConfig[1];
							$resultItem[$responseField] = $inventoryStatus->getQty();
						}

						if($stockStatusRequested) {
							$responseField = $isInStockConfig[1];
							$resultItem[$responseField] = $inventoryStatus->getIsInStock();
						}
					}
				}

				// ATTRIBUTE SET NAME
				$attributeSetNameRequested = $attributeSetNameConfig[0];
				if($attributeSetNameRequested) {

					$attributeSet = Mage::getModel(self::ATTRIBUTE_SET_MODEL)->load($productToRetrieve->getAttributeSetId());
					if (!empty($attributeSet)) {

						$attributeSetName = $attributeSet->getAttributeSetName();
						if($scrubAttributeSetName) {
							$attributeSetName = $this->scrubData($attributeSetName);
						}

						$responseField = $attributeSetNameConfig[1];
						$resultItem[$responseField] = $attributeSetName;
					}
				}

				// CATEGORY BREADCRUMB
				$categoryBreadCrumbRequested = $categoryBreadCrumbConfig[0];
				if($categoryBreadCrumbRequested) {

					$categoryIds = $productToRetrieve->getCategoryIds();

					if (!empty($categoryIds)) {

						$categoryBreadcrumb = '';
						foreach($categoryIds as $categoryId) {

							$category = Mage::getModel(self::CATALOG_CATEGORY_MODEL)->setStoreId($storeId)->load($categoryId);

							if(!empty($category) && $category->getId()) {
								$categoryBreadcrumb .= $category->getData(self::CATEGORY_NAME_FIELD) . self::CATEGORY_SEPARATOR;
							}
						}

						$categoryBreadcrumb = preg_replace('/' . self::CATEGORY_SEPARATOR . '$/', '', $categoryBreadcrumb);

						$responseField = $categoryBreadCrumbConfig[1];
						$resultItem[$responseField] = $categoryBreadcrumb;
					}
				}

				// MANUFACTURER NAME
				if($manufacturerNameRequested) {

					$manufacturer = $productToRetrieve->getResource()->getAttribute(self::MANUFACTURER_FIELD);
					if (!empty($manufacturer)) {

						$manufacturerName = $manufacturer->getFrontend()->getValue($productToRetrieve);
						$manufacturerNameNullValue = $manufacturerNameConfig[2];
						if(empty($manufacturerName) || $manufacturerName == $manufacturerNameNullValue) {
							$manufacturerName = '';
						}
						$responseField = $manufacturerNameConfig[1];
						$resultItem[$responseField] = $manufacturerName;
					}
				}

				// RETRIEVE CONFIGURABLE ITEMS PARENT IDS
				// We need to retrieve parent ids if either of the following fields are requested :
				// - absolute url
				// - absolute image url
				// - parent SKU
				$parentSKURequested = $parentSKUConfig[0];
				$configurableItemsParentIds = array();
				if($absoluteUrlRequested || $absoluteImageUrlRequested || $parentSKURequested) {
					$configurableItemsParentIds = Mage::getModel(self::CONFIGURABLE_PRODUCT_MODEL)->getParentIdsByChild($productIdToRetrieve);
				}

				// ABSOLUTE URL & IMAGE
				if($absoluteUrlRequested || $absoluteImageUrlRequested) {

					$productUrl = $productToRetrieve->getUrlPath();
					$productImage = $productToRetrieve->getImage();

					$noSelectionValue = $absoluteImageUrlConfig[2];

					//If it's a simple product and it's NOT visible then we are getting the URL/ImageURL from the parent (configurable/grouped) product
					if($productToRetrieve->getTypeId() == 'simple' && $productToRetrieve->getData(self::VISIBILITY_FIELD) == 1)
					{
						//Checking if the product is a child of a "configurable" product
						$parentProductIds = $configurableItemsParentIds;

						//Checking if the product is a child of a "grouped" product
						if(sizeof($parentProductIds) < 1) {
							$parentProductIds = Mage::getModel(self::GROUPED_PRODUCT_MODEL)->getParentIdsByChild($productIdToRetrieve);
						}

						//Setting the URL SEO to the parent URL if a parent is found
						if(isset($parentProductIds[0]))
						{
							$firstParentProduct = Mage::getModel(self::CATALOG_PRODUCT_MODEL)->load($parentProductIds[0]);
							$productUrl = $firstParentProduct->getUrlPath();

							if($productImage == "" || $productImage == $noSelectionValue) {
								$productImage = $firstParentProduct->getImage();
							}
						}
						//Blanking-out the URL/Image URL since items that are not visible and are not associated with a parent
						else
						{
							$productUrl = null;
							$productImage = null;
						}
					}

					if($absoluteUrlRequested && !empty($productUrl)) {
						$responseField = $absoluteUrlConfig[1];
						$resultItem[$responseField] = $baseUrl . $productUrl;
					}

					if($absoluteImageUrlRequested && !empty($productImage) && $productImage != $noSelectionValue) {
						$responseField = $absoluteImageUrlConfig[1];
						$resultItem[$responseField] = $imageBaseURL . $productImage;
					}
				}

				// CONFIGURABLE ITEMS PARENT SKU
				if($parentSKURequested) {

					$parentSKUS = array();
					foreach ($configurableItemsParentIds as $parentId) {
						$parentSKUS[] = Mage::getModel(self::CATALOG_PRODUCT_MODEL)->load($parentId)->getData('sku');
					}

					$responseField = $parentSKUConfig[1];
					$resultItem[$responseField] = $parentSKUS;
				}

                // ADDITIONAL IMAGES
                $additionalImagesRequested = $additionalImagesConfig[0];
                if($additionalImagesRequested) {

                    $additionalImageURLs = array();
                    foreach (Mage::getModel('catalog/product')->load($productIdToRetrieve)->getMediaGalleryImages() as $image) {
                        $additionalImageURLs[] = $image['url'];
                    }

                    $resultItem[$additionalImagesConfig[1]] = $additionalImageURLs;
                }

                $resultItems[] = $resultItem;
			}
		}

		return $resultItems;
    }

	private function getProductsFilteredByStockQuantity(
		$filters,
		$stockQuantityFilterAmount,
		$store,
		$attributes,
		$customAttributes,
		$manufacturerNameRequested,
		$absoluteUrlRequested,
		$absoluteImageUrlRequested,
		$pageNumber,
		$productsPerPage
	) {

		$filteredProductsCollection =
			Mage::getModel(self::CATALOG_PRODUCT_MODEL)
					->getCollection()
					->joinField(
						'qty',
						'cataloginventory/stock_item',
						'qty',
						'product_id=entity_id',
						'{{table}}.stock_id=1',
						'left'
					)
					->addAttributeToFilter('qty', array('gteq' => $stockQuantityFilterAmount))
					->addStoreFilter($store);

		if (is_array($filters)) {
			try {
				foreach ($filters as $field => $value) {
					if (isset($this->_filtersMap[$field])) {
						$field = $this->_filtersMap[$field];
					}
					$filteredProductsCollection->addFieldToFilter($field, $value);
				}
			} catch (Mage_Core_Exception $e) {
				$this->_fault('filters_invalid', $e->getMessage());
			}
		}

		if($pageNumber != 0) {
			$filteredProductsCollection->setPage($pageNumber, $productsPerPage);
		}

		if (is_array($attributes)) {
			foreach ($attributes as $attribute) {
				$filteredProductsCollection->addAttributeToSelect($attribute);
			}
		}

		if (is_array($customAttributes)) {
			foreach ($customAttributes as $attribute) {
				$filteredProductsCollection->addAttributeToSelect($attribute);
			}
		}

		if($manufacturerNameRequested) {
			$filteredProductsCollection->addAttributeToSelect(self::MANUFACTURER_FIELD);
		}

		if($absoluteUrlRequested) {
			$filteredProductsCollection->addAttributeToSelect('url_path');
		}

		if($absoluteImageUrlRequested) {
			$filteredProductsCollection->addAttributeToSelect('image');
		}

		if($absoluteUrlRequested || $absoluteImageUrlRequested) {
			$filteredProductsCollection->addAttributeToSelect(self::VISIBILITY_FIELD);
		}

		return $filteredProductsCollection;
	}

	//Scrubbing various unwanted characters
	private function scrubData($fieldValue)
	{
		$fieldValue = str_replace(chr(1), "", $fieldValue);
		$fieldValue = str_replace(chr(2), "", $fieldValue);
		$fieldValue = str_replace(chr(3), "", $fieldValue);
		$fieldValue = str_replace(chr(4), "", $fieldValue);
		$fieldValue = str_replace(chr(5), "", $fieldValue);
		$fieldValue = str_replace(chr(6), "", $fieldValue);
		$fieldValue = str_replace(chr(7), "", $fieldValue);
		$fieldValue = str_replace(chr(10), " ", $fieldValue);
		$fieldValue = str_replace(chr(11), " ", $fieldValue);
		$fieldValue = str_replace(chr(13), " ", $fieldValue);
		$fieldValue = str_replace(chr(17), " ", $fieldValue);
		$fieldValue = str_replace(chr(18), " ", $fieldValue);
		$fieldValue = str_replace(chr(19), " ", $fieldValue);
		$fieldValue = str_replace(chr(20), " ", $fieldValue);
		$fieldValue = str_replace(chr(21), " ", $fieldValue);
		$fieldValue = str_replace(chr(22), " ", $fieldValue);
		$fieldValue = str_replace(chr(23), " ", $fieldValue);
		$fieldValue = str_replace(chr(24), " ", $fieldValue);
		$fieldValue = str_replace(chr(25), " ", $fieldValue);
		$fieldValue = str_replace(chr(26), " ", $fieldValue);
		$fieldValue = str_replace(chr(27), " ", $fieldValue);
		$fieldValue = str_replace(chr(28), " ", $fieldValue);
		$fieldValue = str_replace(chr(29), " ", $fieldValue);
		$fieldValue = str_replace(chr(30), " ", $fieldValue);
		$fieldValue = str_replace(chr(31), " ", $fieldValue);
		$fieldValue = str_replace("\r", " ", $fieldValue);
		$fieldValue = str_replace("\n", " ", $fieldValue);
		$fieldValue = str_replace("\r\n", " ", $fieldValue);
		$fieldValue = str_replace("\t", "    ", $fieldValue);
		return $fieldValue;
	}
}