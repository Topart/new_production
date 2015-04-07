<?php

abstract class Springbot_Combine_Model_Harvest
{
	abstract public function getMageModel();
	abstract public function getParserModel();
	abstract public function getApiController();
	abstract public function getApiModel();

	private $_api;
	private $_collection;
	private $_model;
	private $_total = 0;
	private $_segmentQueue = array();
	private $_segmentSize = 250;
	private $_segmentMin = 0;
	private $_segmentMax = 0;
	private $_delete = false;
	private $_storeId = null;
	private $_dataSource;


	public function __construct(Springbot_Combine_Model_Api $api, Varien_Data_Collection $collection, $dataSource)
	{
		$this->_api = $api;
		$this->_collection = $collection;
		$this->_dataSource = $dataSource;
	}

	/**
	 * Return the row name for the given
	 *
	 * @return string the name of the unique id column for the entity
	 */
	public function getRowId()
	{
		return 'entity_id';
	}

	/**
	 * Iterate through all entities inn the collection and call the step() method
	 * which will post the JSON entities to the API.
	 *
	 * @return Springbot_Combine_Model_Harvest
	 */
	public function harvest()
	{
		//if ($this->getCollection()->getCount()) {
			Mage::getSingleton('core/resource_iterator')->walk(
				$this->getCollection()->getSelect(),
				array(array($this, 'step'))
			);

			// Post leftover segment
			$this->_total += count($this->_segmentQueue);
			$this->postSegment();
		//}

		return $this;
	}

	/**
	 * Set delete param for all records
	 *
	 * @return Springbot_Combine_Model_Harvest
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
		$parsed = array($this->parse($model)->getData());
		$payload = $this->getApi()->wrap($this->getApiModel(), $parsed);
		$this->getApi()->reinit()->call($this->getApiController(), $payload);
	}

	/**
	 * Push a model onto the segment queue
	 *
	 * @param Mage_Core_Model_Abstract $model
	 * @return Springbot_Combine_Model_Harvest_Abstract
	 */
	public function push(Mage_Core_Model_Abstract $model)
	{
		$this->_segmentQueue[] = $this->parse($model);
		return $this;
	}

	/**
	 * Step callback referenced in harvester. Posts the segment (limited by the
	 * defined segment size)
	 *
	 * @param array $args
	 */
	public function step($args)
	{
		if (count($this->_segmentQueue) >= $this->getSegmentSize()) {
			$this->_total += $this->getSegmentSize();
			$this->postSegment();
		}

		try {
			if (isset($args['row'])) {
				$id = $this->_getRowId($args['row']);
				$model = $this->loadMageModel($id);
				$this->_segmentQueue[] = $this->parse($model);
			}
		}
		catch (Exception $e) {
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
		$parser = Mage::getModel($this->getParserModel(), $model);
		if ($this->getStoreId()) {
			$parser->setMageStoreId($this->getStoreId());
		}
		$parser->setDataSource($this->getDataSource());
		if($this->_delete) {
			$parser->setIsDeleted(true);
		}
		return $parser->getData();
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
			$this->_model = Mage::getModel($this->getMageModel());
		}
		return $this->_model->load($entityId);
	}

	/**
	 * Post segment to api
	 *
	 */
	public function postSegment()
	{
		if (count($this->_segmentQueue) > 0) {
			$payload = $this->getApi()->wrap($this->getApiModel(), $this->_segmentQueue);
			$this->getApi()->reinit()->call($this->getApiController(), $payload);
			$this->_segmentQueue = array();
		}
	}

	/**
	 * Set the current store id
	 *
	 * @param int $id
	 */
	public function setStoreId($id)
	{
		$this->_storeId = $id;
		return $this;
	}

	public function getDataSource()
	{
		return $this->_dataSource;
	}

	public function setDelete($bool)
	{
		$this->_delete = $bool;
		return $this;
	}

	public function getDelete()
	{
		return $this->_delete;
	}

	public function getHarvesterName()
	{
		return ucfirst($this->getHarvesterName());
	}

	public function getCollection()
	{
		return $this->_collection;
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

	public function getSegmentSize()
	{
		return $this->_segmentSize;
	}

	public function getApi()
	{
		return $this->_api;
	}

	public function getStoreId()
	{
		return $this->_storeId;
	}

	/**
	 * Gets row id based on class config
	 *
	 * @param array $row
	 * @return int
	 */
	private function _getRowId($row)
	{
		$id = null;
		if(isset($row[$this->getRowId()])) {
			$id = $row[$this->getRowId()];
			$this->_setSegmentMinMax($id);
		}
		return $id;
	}

	/**
	 * Set min/max for current segment
	 *
	 * @param int $id
	 */
	private function _setSegmentMinMax($id)
	{
		if($id < $this->_segmentMin || !$this->_segmentMin) {
			$this->_segmentMin = $id;
		}
		if($id > $this->_segmentMax) {
			$this->_segmentMax = $id;
		}
	}
}
