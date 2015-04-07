<?php

class Springbot_Combine_Model_Parser_Product extends Springbot_Combine_Model_Parser
{
	protected $_accessor = '_product';
	protected $_product;

	public function __construct(Mage_Catalog_Model_Product $product)
	{
		$this->_product = $product;
		$this->_parse();
	}

	public function _parse()
	{
		$p = $this->_product;
		$categories = Springbot_Util_Categories::forProduct($this->_product);

		$this->setData(array(
			'store_id' => $this->getSpringbotStoreId(),
			'entity_id' => $p->getId(),
			'sku' => $this->_getSku(),
			'sku_fulfillment' => $p->getSku(),
			'category_ids' => $p->getCategoryIds(),
			'root_category_ids' => $categories->getRoots(),
			'all_category_ids' => $categories->getAll(),
			'full_description' => $p->getDescription(),
			'short_description' => $p->getShortDescription(),
			'image_url' => $this->getImageUrl(),
			'landing_url' => $this->getLandingUrl(),
			'name' => $p->getName(),
			'url_key' => $p->getUrlKey(),
			'is_deleted' => false,
			'status' => $p->getStatus(),
			'created_at' => $this->_formatDateTime($p->getCreatedAt()),
			'updated_at' => $this->_formatDateTime($p->getUpdatedAt()),
			'catalog_created_at' => $this->_formatDateTime($p->getCreatedAt()),
			'catalog_updated_at' => $this->_formatDateTime($p->getUpdatedAt()),
			'json_data' => array(
				'unit_price' => $p->getPrice(),
				'msrp' => $p->getMsrp(),
				'sale_price' => $p->getSpecialPrice(),
				'unit_cost' => 0,
				'image_label' => $p->getImageLabel(),
				'unit_wgt' => $p->getWeight(),
				'type' => $p->getTypeId(),
				'visibility' => $p->getVisibility(),
				'cat_id_list' => $this->_implodeCategoryIds(),
			),
			'custom_attribute_set_id' => $p->getAttributeSetId(),
			'custom_attributes' => $this->getCustomAttributes(),
		));
		return parent::_parse();
	}



	public function getCustomAttributes()
	{
		return $this->_getHelper()->getCustomAttributes($this->_product);
	}

	protected function _implodeCategoryIds()
	{
		$ids = $this->_product->getCategoryIds();

		if($ids && count($ids) > 0) {
			$ids = implode(',', $ids);
		}
		return $ids;
	}

	protected function _getSku()
	{
		$parents = Mage::helper('combine/parser')->getParentSkus($this->_product->getId());
		if(sizeof($parents) > 0) {
			$sku = implode('|', $parents);
		} else {
			$sku = $this->_getSkuFailsafe($this->_product);
		}
		return $sku;
	}

	public function getImageUrl()
	{
		return $this->_getHelper()->getImageUrl($this->_product);
	}

	public function getLandingUrl()
	{
		return parent::_getLandingUrl($this->_product);
	}
}

