<?php

class Springbot_BoneCollector_Model_HarvestProduct_Observer extends Springbot_BoneCollector_Model_HarvestAbstract
{
	protected $_product;

	protected $_attributes = array(
		'entity_id',
		'sku',
		'attribute_set_id',
		'description',
		'full_description',
		'short_description',
		'image',
		'url_key',
		'small_image',
		'thumbnail',
		'status',
		'visibility',
		'price',
		'special_price',
		'image_label',
		'name',
	);

	public function onProductSaveAfter($observer)
	{
		try {
			$this->_product = $observer->getEvent()->getProduct();

			if ($this->_entityChanged($this->_product)) {
				$this->_initObserver($observer);
				Springbot_Boss::scheduleJob('post:product', array('i' => $this->_product->getId()), Springbot_Services::LISTENER, 'listener');
			}

		} catch (Exception $e) {
			Springbot_Log::error($e);
		}
	}

	public function onProductDeleteBefore($observer)
	{
		$this->_initObserver($observer);
		try{
			$this->_product   = $observer->getEvent()->getProduct();
			$entityId = $this->_product->getId();
			$helper = Mage::helper('combine/harvest');
			foreach($helper->mapStoreIds($this->_product) as $mapped) {
				$sbId = $helper->getSpringbotStoreId($mapped->getStoreId());
				$post[] = array(
					'store_id' => $sbId,
					'entity_id' => $entityId,
					'sku' => $this->_getSkuFailsafe($this->_product),
					'is_deleted' => true,
				);
			}
			Mage::helper('combine/harvest')->deleteRemote($post, 'products');
		} catch (Exception $e) {
			Springbot_Log::error($e);
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

	protected function _getAttributesToListenFor($extras = array())
	{
		return parent::_getAttributesToListenFor(
			Mage::helper('combine/parser')->getCustomAttributeNames($this->_product)
		);

	}
}
