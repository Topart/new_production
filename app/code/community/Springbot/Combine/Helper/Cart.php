<?php

class Springbot_Combine_Helper_Cart extends Mage_Core_Helper_Abstract
{
	public function setQuote($quoteId, $suppliedSecurityHash) {
		if (Mage::getStoreConfig('springbot/cart_restore/do_restore') == 1) {
			if ($quote = Mage::getModel('sales/quote')->load($quoteId)) {
				$cartCount = Mage::helper('checkout/cart')->getSummaryCount();
				if ($cartCount == 0) {

					$quote->setIsActive(true)->save();
					$token = Mage::getStoreConfig('springbot/config/security_token');

					$correctSecurityHash = sha1($quoteId . $token);
					if ($suppliedSecurityHash == $correctSecurityHash) {

						if (Mage::getStoreConfig('springbot/cart_restore/retain_coupon') == 0) {
							$quote->setCouponCode('');
							$quote->save();
						}

						Mage::getSingleton('checkout/session')->setQuoteId($quoteId);
					}
				}
			}
		}
	}
}
