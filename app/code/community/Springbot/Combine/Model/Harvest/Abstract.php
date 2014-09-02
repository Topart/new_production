<?php

class Springbot_Combine_Model_Harvest_Abstract
{
	protected $_collection;
	protected $_iterator;
	protected $_https;
	protected $_model;
	protected $_parser;
	protected $_parserModel;
	protected $_segmentQueue = array();
	protected $_total = 0;
	protected $_apiController;
	protected $_apiModel;
	protected $_mageModel;
	protected $_rowId = 'entity_id';
	protected $_segmentSize = 250;
	protected $_segmentMin = 0;
	protected $_segmentMax = 0;
	protected $_delete = false;
	protected $_storeId;
	protected $_dataSource;

	/**
	 * Central controller for harvest
	 *
	 * @return Springbot_Combine_Model_Harvest_Abstract
	 */
	public function harvest()
	{
		if($this->getCount()) {
			$this->_getIterator()->walk(
						$this->getSelect(),
						array(array($this, 'step'))
					);
			// Post leftover segment
			$this->_total += count($this->_segmentQueue);
			$this->postSegment();
		}
		return $this;
	}

	/**
	 * Set delete param for all records
	 *
	 * @return Springbot_Combine_Model_Harvest_Abstract
	 */
	public function delete()
	{
		$this->_delete = true;
		return $this->harvest();
	}

	/**
	 * Post single defined model
	 *
	 * We must post as a single element in array to handle downstream
	 * formatting concerns.
	 *
	 * @param Mage_Core_Model_Abstract $model
	 */
	public function post($model)
	{
		$parsed = array($this->parse($model));
		$payload = $this->_getApi()->wrap($this->_getApiModel(), $parsed);
		$this->_getApi()->reinit()->call($this->_getApiController(), $payload);
	}

	/**
	 * Push a model onto the segment queue
	 *
	 * @param Mage_Core_Model_Abstract $model
	 * @return Springbot_Combine_Model_Harvest_Abstract
	 */
	public function push($model)
	{
		unset($this->_parser); //reinit
		$this->setDataSource(Springbot_Boss::SOURCE_OBSERVER);
		$this->_segmentQueue[] = $this->parse($model);
		return $this;
	}

	/**
	 * Gets row id based on class config
	 *
	 * @param array $row
	 * @return int
	 */
	protected function _getRowId($row)
	{
		$id = null;
		if(isset($row[$this->_rowId])) {
			$id = $row[$this->_rowId];
			$this->_setSegmentMinMax($id);
		}
		return $id;
	}

	/**
	 * Set min/max for current segment
	 *
	 * @param int $id
	 */
	protected function _setSegmentMinMax($id)
	{
		if($id < $this->_segmentMin || !$this->_segmentMin) {
			$this->_segmentMin = $id;
		}
		if($id > $this->_segmentMax) {
			$this->_segmentMax = $id;
		}
	}

	/**
	 * Step callback referenced in harvester
	 *
	 * @param array $args
	 */
	public function step($args)
	{
		if(count($this->_segmentQueue) >= $this->_getMaxSegmentSize()) {
			$this->_total += $this->_getMaxSegmentSize();
			$this->postSegment();
		}

		try {
			if(isset($args['row'])) {
				$id = $this->_getRowId($args['row']);
				$model = $this->loadMageModel($id);
				$this->_segmentQueue[] = $this->parse($model);
			}
		} catch (Exception $e) {
			Springbot_Log::error($e);
		}
	}

	/**
	 * Parse caller for dependent parser method
	 *
	 * @param Mage_Core_Model_Abstract $model
	 * @return Zend_Json_Expr
	 */
	public function parse($model)
	{
		$parsed = $this->_getParser($model)->parse($model);
		if($this->_delete) {
			$parsed->setIsDeleted(true);
		}
		$parsed->setDataSource($this->getDataSource());
		$json = $parsed->toJson();
		return $parsed->getData();
	}

	/**
	 * Loads mage model to parse
	 *
	 * @param int $entityId
	 * @return Mage_Core_Model_Abstract
	 */
	public function loadMageModel($entityId)
	{
		if(!isset($this->_model)) {
			$this->_model = Mage::getModel($this->_getMageModel());
		}
		return $this->_model->load($entityId);
	}

	/**
	 * Post segment to api
	 */
	public function postSegment()
	{
		if(count($this->_segmentQueue) > 0) {
			$payload = $this->_getApi()->wrap($this->_getApiModel(), $this->_segmentQueue);
			$this->_getApi()->reinit()->call($this->_getApiController(), $payload);

			$this->_clearSegment();
		}
	}

	protected function _clearSegment()
	{
		unset($this->_segmentQueue);
	}

	public function setStoreId($id)
	{
		$this->_storeId = $id;
		return $this;
	}

	public function setDataSource($source)
	{
		$this->_dataSource = $source;
		return $this;
	}

	public function setDelete($bool)
	{
		$this->_delete = $bool;
		return $this;
	}

	public function getHarvesterName()
	{
		return ucfirst($this->_apiModel);
	}

	public function getCollection()
	{
		if(!isset($this->_collection)) {
			throw new Exception("Collection not found!");
		}
		return $this->_collection;
	}

	public function setCollection(Varien_Data_Collection $collection)
	{
		$this->_collection = $collection;
		return $this;
	}

	public function getSelect()
	{
		return $this->getCollection()->getSelect();
	}

	public function getCount()
	{
		return $this->getCollection()->getSize();
	}

	public function getProcessedCount()
	{
		return $this->_total;
	}

	public function getSegmentMin()
	{
		return $this->_segmentMin;
	}

	public function getSegmentMax()
	{
		return $this->_segmentMax;
	}

	public function getDataSource()
	{
		return $this->_dataSource;
	}

	protected function _getMageModel()
	{
		if(!isset($this->_mageModel)) {
			throw new Exception('Please set Magento model to parse!');
		}
		return $this->_mageModel;
	}

	protected function _getParser($model)
	{
		if(!isset($this->_parserModel)) {
			throw new Exception('Please set parser type.');
		}
		if(!isset($this->_parser)) {
			$this->_parser = Mage::getModel($this->_parserModel, $model);
		}
		return $this->_parser;
	}

	protected function _getApiController()
	{
		if(!isset($this->_apiController)) {
			throw new Exception('Please set api controller to send to.');
		}
		return $this->_apiController;
	}

	protected function _getApiModel()
	{
		if(!isset($this->_apiModel)) {
			throw new Exception('Please set remote model to send to.');
		}
		return $this->_apiModel;
	}

	protected function _getMaxSegmentSize()
	{
		return $this->_segmentSize;
	}

	protected function _getApi()
	{
		if(!isset($this->_api)) {
			$this->_api = Mage::getModel('combine/api');
		}
		return $this->_api;
	}

	protected function _getIterator()
	{
		if(!isset($this->_iterator)) {
			$this->_iterator = Mage::getSingleton('core/resource_iterator');
		}
		return $this->_iterator;
	}
}
