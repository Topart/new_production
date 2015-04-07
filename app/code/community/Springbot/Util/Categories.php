<?php

class Springbot_Util_Categories
{
	protected $_product;
	protected $_paths = array();
	protected $_pathsBuilt = false;

	public function __construct(Mage_Catalog_Model_Product $product)
	{
		$this->_product = $product;
	}

	public static function forProduct($product)
	{
		return new Springbot_Util_Categories($product);
	}

	public function getRoots()
	{
		$roots = array();
		foreach($this->_getPaths() as $path) {
			if(isset($path[2])) {
				$roots[] = $path[2];
			}
		}
		return array_values(array_unique($roots));
	}

	public function getAll()
	{
		$categories = array();
		foreach($this->_getPaths() as $path) {
			$categories = array_merge($path, $categories);
		}
		sort($categories);
		return array_values(array_unique($categories));
	}

	protected function _getPaths()
	{
		if(!$this->_pathsBuilt) {
			$_paths = $this->_getColumnValues();
			foreach($_paths as $_path) {
				$path = explode('/', $_path);
				if(count($path) > 2) {
					$this->_paths[] = $path;
				}
			}
			$this->_pathsBuild = true;
		}
		return $this->_paths;
	}

	/*
	 * This might not happen in practice, but we were seeing
	 * tests return the CategoryCollection as an object of
	 * type Varien_Data_Tree_Node_Collection, which does not
	 * have getColumnValues.  This is mostly so the tests will
	 * pass, but it doesn't hurt anyting to leave it in.
	 */
	protected function _getColumnValues()
	{
		$collection = $this->_product->getCategoryCollection();
		if(method_exists($collection, 'getColumnValues')) {
			return $collection->getColumnValues('path');
		} else {
			return array();
		}
	}
}
