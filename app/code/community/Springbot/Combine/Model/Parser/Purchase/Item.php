<?php

class Springbot_Combine_Model_Parser_Purchase_Item extends Springbot_Combine_Model_Parser
{
	protected $_item;
	protected $_accessor = '_item';
	protected $_actualProduct;
	protected $_parentProduct;

	public function __construct(Mage_Sales_Model_Order_Item $item)
	{
		$this->_item = $item;
		$this->_actualProduct = null;
		$this->_parentProduct = null;
		$this->_parse();
	}

	public function _parse()
	{
		$item = $this->_item;

		$parent = $this->getParentProduct();
		$actual = $this->getActualProduct();

		$categories = Springbot_Util_Categories::forProduct($parent);

		$this->_data = array(
			'sku' => $this->_getSkuFailsafe($parent),
			'sku_fulfillment' => $item->getSku(),
			'qty_ordered' => $item->getQtyOrdered(),
			'landing_url' => $this->getLandingUrl(),
			'image_url' => $this->getImageUrl(),
			'wgt' => $item->getWeight(),
			'name' => $item->getName(),
			'desc' => $item->getDescription(),
			'sell_price' => $this->_getBaseAmt('row_total', $item),
			'product_id' => (int) $item->getProductId(),
			'product_type' => $item->getProductType(),
			'category_ids' => $parent->getCategoryIds(),
			'root_category_ids' => $categories->getRoots(),
			'all_category_ids' => $categories->getAll(),
			'attribute_set_id' => (int) $actual->getAttributeSetId(),
			'attributes' => $this->_getProductAttributes(),
		);
	}

	public function getParentProduct()
	{
		$item = $this->_item;

		if(!isset($this->_parentProduct))
		{
			if($config = $item->getProductOptionByCode('super_product_config')) {
				$parentProductId = isset($config['product_id']) ? $config['product_id'] : null;
			}
			else if($item->hasParentItemId()) {
				$parentProductId = $item->getParentItem()->getProductId();
			}
			if(!isset($parentProductId)) {
				$parentProductId = $item->getProductId();
			}
			$this->_parentProduct = Mage::getModel('catalog/product')->load($parentProductId);
		}
		return $this->_parentProduct;
	}

	public function getActualProduct()
	{
		$item = $this->_item;

		if(!isset($this->_actualProduct)) {
			if($item->getProductType() == 'simple') {
				$this->_actualProduct = Mage::getModel('catalog/product')->load($item->getProductId());
			}
			else {
				$this->_actualProduct = Mage::getModel('catalog/product')->load($item->getProductId());

				foreach($item->getOrder()->getAllItems() as $_item) {
					if($item->getSku() == $_item->getSku()) {
						$this->_actualProduct = Mage::getModel('catalog/product')->load($_item->getProductId());
					}
				}
			}
		}
		return $this->_actualProduct;
	}

	protected function _getProductAttributes()
	{
		return $this->_getHelper()->getCustomAttributes($this->getActualProduct(), 50);
	}

	public function getLandingUrl()
	{
		$product = $this->getActualProduct();

		if(!$this->_getHelper()->isAccessible($product)) {
			$product = $this->getParentProduct();
		}

		return $this->_getLandingUrl($product);
	}

	public function getImageUrl()
	{
		$product = $this->getActualProduct();

		if(!$this->_getHelper()->hasImage($product)) {
			$product = $this->getParentProduct();
		}

		return $this->_getHelper()->getImageUrl($product);
	}
}
