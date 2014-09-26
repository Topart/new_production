<?php
/**
 * BoneCollector Event Listener (Product Harvest)
 *
 * @version		v1.0.0 - 12/28/2012
 *
 * @category    Magento Integrations
 * @package     springbot
 * @author 		William Seitz
 * @division	SpringBot Integration Team
 * @support		magentosupport@springbot.com
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
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
	);

	public function harvestProduct($observer)
	{
		try {
			$this->_product = $observer->getEvent()->getProduct();

			if ($this->_entityChanged($this->_product)) {
				$this->_initObserver($observer);
				Springbot_Boss::scheduleJob('post:product', array('i' => $this->_product->getId()), Springbot_Services_Priority::LISTENER, 'listener');
			}

		} catch (Exception $e) {
			Mage::logException($e);
		}
	}

	public function deleteProduct($observer)
	{
		$this->_initObserver($observer);

		try{
			$this->_product   = $observer->getEvent()->getProduct();
			$entity_id = $this->_product->getId();
			$storeIds  = $this->_product->getStoreIds();

			foreach(Mage::helper('combine/harvest')->mapStoreIds($this->_product) as $mapped) {
				$post[] = array(
					'store_id' => $mapped->getStoreId(),
					'entity_id' => $entity_id,
					'sku' => $this->_getSkuFailsafe($this->_product),
					'is_deleted' => true,
				);
			}

			Mage::helper('combine/harvest')->deleteRemote($post, 'products');
		} catch (Exception $e) {
			Mage::logException($e);
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
