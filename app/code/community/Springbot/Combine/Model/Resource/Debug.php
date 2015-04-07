<?php

class Springbot_Combine_Model_Resource_Debug extends Springbot_Combine_Model_Resource_Abstract
{

	public function _construct()
	{
	}

	public function getProductsRaw()
	{
		$readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
		$productTableName = Mage::getSingleton('core/resource')->getTableName('catalog_product_entity');
		$query = "SELECT COUNT(*) as `count`, `type_id` FROM {$productTableName} GROUP BY `type_id`";
		return $readConnection->fetchAll($query);
	}

	public function getCategoriesRaw()
	{
		$readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
		$categoryTableName = Mage::getSingleton('core/resource')->getTableName('catalog_category_entity');
		$query = "SELECT COUNT(*) as `count` FROM {$categoryTableName}";
		return $readConnection->fetchAll($query);
	}

	public function getCustomersRaw()
	{
		$readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
		$customerTableName = Mage::getSingleton('core/resource')->getTableName('customer_entity');
		$query = "SELECT COUNT(*) as `count`, `store_id` FROM {$customerTableName} GROUP BY `store_id`";
		return $readConnection->fetchAll($query);
	}

	public function getSubscribersRaw()
	{
		$readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
		$subscriberTableName = Mage::getSingleton('core/resource')->getTableName('newsletter_subscriber');
		$query = "SELECT COUNT(*) as `count`, `store_id` FROM {$subscriberTableName} GROUP BY `store_id`";
		return $readConnection->fetchAll($query);
	}

	public function getPurchasesRaw()
	{
		$readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
		$ordersTableName = Mage::getSingleton('core/resource')->getTableName('sales_flat_order');
		$query = "SELECT COUNT(*) as `count`, `store_id` FROM {$ordersTableName} GROUP BY `store_id`";
		return $readConnection->fetchAll($query);
	}

	public function getCartsRaw()
	{
		$readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
		$quotesTableName = Mage::getSingleton('core/resource')->getTableName('sales_flat_quote');
		$query = "SELECT COUNT(*) as `count`, `store_id` FROM {$quotesTableName} GROUP BY `store_id`";
		return $readConnection->fetchAll($query);
	}

	public function getGuestsRaw()
	{
		$readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
		$quotesTableName = Mage::getSingleton('core/resource')->getTableName('sales_flat_order');
		$query = "SELECT COUNT(*) AS `count`, `store_id` FROM (SELECT DISTINCT (customer_email), `store_id` FROM `{$quotesTableName}` GROUP BY store_id, customer_email) `customers` GROUP BY `store_id`";
		return $readConnection->fetchAll($query);
	}

}
