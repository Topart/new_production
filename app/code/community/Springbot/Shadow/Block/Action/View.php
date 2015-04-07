<?php

class Springbot_Shadow_Block_Action_View extends Mage_Core_Block_Template
{
	protected function _toHtml()
	{
		return '<img src="' . $this->_getPixelUrl() . '"style="position:absolute; visibility:hidden">';
	}

	private function _getPixelUrl()
	{
		$params = array(
			'store_id' => Mage::app()->getStore()->getStoreId(),
			'sku' => Mage::helper('combine/parser')->getTopLevelSku(Mage::registry('current_product')),
			'page_url' => Mage::helper('core/url')->getCurrentUrl(),
			'category_id' => Mage::helper('combine')->getLastCategoryId(),
		);
		return Mage::getUrl('springbot_update/action/view', array('_query' => $params));
	}
}
