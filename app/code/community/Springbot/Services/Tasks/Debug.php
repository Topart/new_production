<?php

class Springbot_Services_Tasks_Debug extends Springbot_Services
{
	public function run()
	{
		Mage::getConfig()->cleanCache();
		$resource = Mage::getResourceModel('combine/debug');

		return array(
			'customers' => $resource->getCustomersRaw(),
			'guests' => $resource->getGuestsRaw(),
			'subscribers' => $resource->getSubscribersRaw(),
			'products' => $resource->getProductsRaw(),
			'categories' => $resource->getCategoriesRaw(),
			'purchases' => $resource->getPurchasesRaw(),
			'carts' => $resource->getCartsRaw(),
		);
	}
}




