<?php

class Springbot_Combine_Model_Parser_AttributeSet extends Springbot_Combine_Model_Parser
{
	protected $_helper;
	protected $_set;
	protected $_storeId;
	protected $_accessor = '_set';
	protected $_type = 'product';

	public function __construct(Mage_Eav_Model_Entity_Attribute_Set $set)
	{
		$this->_set = $set;
		$this->_parse();
	}

	protected function _parse()
	{
		$this->_data = array(
			'store_id' => $this->getSpringbotStoreId(),
			'attribute_set_id' => $this->_set->getAttributeSetId(),
			'name' => $this->_set->getAttributeSetName(),
			'type' => $this->_type,
			'attribute_items' => $this->_parseAttributes(),
		);

		return parent::_parse();
	}

	protected function _parseAttributes()
	{
		return Mage::helper('combine/attributes')->getParsedAttributesBySet($this->_set);
	}

	protected function _getHelper()
	{
		if(!isset($this->_helper)) {
			$this->_helper = Mage::helper('combine/attributes');
		}
		return $this->_helper;
	}
}
