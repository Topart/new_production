<?php

class Springbot_Combine_Model_Parser_Category extends Springbot_Combine_Model_Parser
{
	protected $_accessor = '_category';
	protected $_category;
	protected $_storeId;

	public function __construct(Mage_Catalog_Model_Category $category)
	{
		$this->_category = $category;
		$this->_parse();
	}

	protected function _parse()
	{
		$this->setData(array(
			'cat_id' => $this->_category->getEntityId(),
			'path' => $this->_category->getPath(),
			'level' => $this->_category->getLevel(),
			'store_id' => $this->getSpringbotStoreId(),
			'cat_name' => $this->_category->getName(),
			'deleted' => $this->_category->getDeleted(),
			'json_data' => array(
				'url_path' => $this->_category->getUrlPath(),
				'is_active' => $this->_category->getIsActive(),
			)
		));
		return parent::_parse();
	}
}
