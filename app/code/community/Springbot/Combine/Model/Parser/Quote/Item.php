<?php

class Springbot_Combine_Model_Parser_Quote_Item extends Springbot_Combine_Model_Parser_Abstract
{
	protected $_item;
	protected $_accessor = '_item';
	protected $_parentProduct;
	protected $_actualProduct;

	public function __construct(Mage_Sales_Model_Quote_Item $item)
	{
		$this->_item = $item;
		$this->_parentProduct = null;
		$this->_actualProduct = null;
		$this->_parse();
	}

	public function parse(Mage_Sales_Model_Quote_Item $item)
	{
		$this->_item = $item;
		$this->_parentProduct = null;
		$this->_actualProduct = null;
		$this->_parse();
		return $this;
	}

	protected function _parse()
	{
		$item = $this->_item;

		$parent = $this->getParentProduct();
		$actual = $this->getActualProduct();

		$this->_data = array(
			'sku' => $this->_getSkuFailsafe($parent),
			'sku_fulfillment' => $item->getSku(),
			'entity_id' => $item->getProductId(),
			'landing_url' => $this->getLandingUrl(),
			'image_url' => $this->getImageUrl(),
			'qty' => $item->getQty(),
			'product_type' => $item->getProductType(),
		);
		return parent::_parse();
	}

	public function getParentProduct()
	{
		if(!isset($this->_parentProduct))
		{
			$item = $this->_item;

			if($type = $item->getOptionByCode('product_type'))
			{
				if($parentProductId = $type->getProductId())
				{
					$this->_parentProduct = Mage::getModel('catalog/product')->load($parentProductId);
				}
			}
			else if($item->hasParentItemId())
			{
				$this->_parentProduct = $item->getParentItem()->getProduct();
			}
			else
			{
				$this->_parentProduct = $item->getProduct();
			}
		}
		return $this->_parentProduct;
	}

	public function getActualProduct()
	{
		if(!isset($this->_actualProduct))
		{
			if($option = $this->_item->getOptionByCode('simple_product'))
			{
				$this->_actualProduct = Mage::getModel('catalog/product')->load($option->getProductId());
			}
			else
			{
				$this->_actualProduct = $this->_item->getProduct();
			}
		}
		return $this->_actualProduct;
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
