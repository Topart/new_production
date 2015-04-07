<?php

class Springbot_Shadow_Model_Listeners_Observer	{

	public function reconstituteCart($observer)
	{
		try {
			if ($quoteId = Mage::app()->getRequest()->getParam('quote_id')) {
				$suppliedSecurityHash = Mage::app()->getRequest()->getParam('sec_key');
				Mage::helper('combine/cart')->setQuote($quoteId, $suppliedSecurityHash);
			}
		}
		catch (Exception $e) {
			Springbot_Log::error($e);
		}
		return;
	}

}
