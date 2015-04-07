<?php

class Springbot_Services_Post_Cart extends Springbot_Services_Post
{
	public function run()
	{
		$quoteId = $this->getEntityId();
		Springbot_Log::debug("Posting quote $quoteId");

		$quote = Mage::getModel('sales/quote');

		// For some reason you have to set the store to load a quote, why??? Varien knows...
		$store = Mage::getModel('core/store')->load($this->getStoreId());
		$quote->setStore($store)->load($quoteId);

		$parser = Mage::getModel('combine/parser_quote', $quote);
		$quoteJson = $parser->toJson();
		Mage::helper('combine')->apiPostWrapped('carts', json_decode($quoteJson), true);
	}

}
