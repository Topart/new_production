<?php

class Springbot_Combine_Helper_Harvest extends Mage_Core_Helper_Abstract
{
	protected $_ignores;
	protected $_defines;
	protected $_rulesBuilt = false;
	protected $_harvestId;

	public function initRemoteHarvest($id)
	{
		$api = Mage::getModel('combine/api');

		Springbot_Log::debug("Query remote service for harvest id");

		$response = $api->get('harvests/new', array('store_id' => $this->getSpringbotStoreId($id)));

		if(isset($response['harvest_id'])) {
			$this->_harvestId = $response['harvest_id'];
		} else {
			Springbot_Log::debug("Harvest id not present in response");
			Springbot_Log::debug($response);
		}

		return $this->_harvestId;
	}

	public function reinit()
	{
		$this->_buildStoreRules();
		return $this;
	}

	/**
	 * Map store ids to given model
	 *
	 * This method intends to apply all appropriate store ids to an object (namely
	 * categories and products), but could be anything.  If the callback fails to
	 * provide any store ids, we failover to provide the originally supplied model.
	 *
	 * @param Varien_Object $model
	 * @param mixed $storeIds
	 * @param string $callback
	 * @return array<Varien_Object>
	 */
	public function mapStoreIds($model, $storeIds = null, $callback = 'getStoreIds')
	{
		$output = array();

		if(!$storeIds) {
			$storeIds = $model->{$callback}();
		}

		foreach($storeIds as $id) {
			if($id) {
				$_model = clone $model;
				$_model = $_model->setStoreId($id)->load($_model->getId());
				$output[] = $_model;
			}
		}

		return !empty($output) ? $output : array($model);
	}


	/**
	 * Get last collection primary id
	 *
	 * @param Varien_Data_Collection $collection
	 * @return int
	 */
	public function getLastCollectionId($collection, $dir = 'DESC')
	{
		$collection->clear();

		$id = $this->getIdFieldName($collection);

		$collection->getSelect()
			->reset(Zend_Db_Select::ORDER)
			->order("$id $dir")
			->limit(1);
		return $collection->getFirstItem()->getData($id);
	}

	public function getFirstCollectionId($collection)
	{
		return $this->getLastCollectionId($collection, 'ASC');
	}

	/**
	 * Get id field name for collection
	 *
	 * Attempt to get id field name (sql primary key) for collection through
	 * existing methods, then failing over to inspecting an the resource itself.
	 * Mainly done this way for subscribers.
	 *
	 * @param Varien_Data_Collection $collection
	 * @param string $default | optional
	 * @return string
	 */
	public function getIdFieldName($collection, $default = 'entity_id')
	{
		if(method_exists($collection, 'getIdFieldName')) {
			$id = $collection->getIdFieldName();
		}

		if(is_null($id) && method_exists($collection, 'getRowIdFieldName')) {
			$id = $collection->getRowIdFieldName();
		}

		if(is_null($id)) {
			try {
				$id = $collection->getResource()->getIdFieldName();
			} catch (Exception $e) {}
		}

		if(is_null($id)) {
			$id = $default;
		}

		return $id;
	}

	/**
	 * Partition collection
	 *
	 * This could be done a little more efficiently through the use of limit and next
	 * commands, but we run the risk of utilizing more memory than we want to for large collections.
	 * In this method we split the collection up, not caring for density, so we might 'harvest'
	 * countless blank segments.
	 *
	 * @param Varien_Data_Collection $collection
	 * @return array<string>
	 */
	public function partitionCollection($collection, $segmentSize = null)
	{
		$class = get_class($collection);
		Springbot_Log::debug("Parititoning {$class} with select:");
		Springbot_Log::debug((string) $collection->getSelect());

		$idFieldName = $this->getIdFieldName($collection);
		$sampleSize = self::getSampleSize();
		$reverseSample = self::getReverseSample();

		$size = is_null($segmentSize) ? $this->getSegmentSize() : $segmentSize;
		$segments = array();

		// If getting just a sample of each entity, get the $sampleSize most recent entities
		if ($sampleSize && !$reverseSample) {
			$maxEntities = $sampleSize;
		}
		else {
			$maxEntities = null;
		}

		$lastId = $this->getLastCollectionId($collection);
		$firstId = $this->getFirstCollectionId($collection);

		Springbot_Log::debug("Partitioning collection from $firstId to $lastId");

		if ($lastId) {
			$blockCount = 0;
			do {
				$nextId = $this->_getLowestEntityId($collection, $idFieldName, $lastId, $size);

				if($nextId < $firstId) {
					$nextId = $firstId;
				} else if (!$nextId) {
					$nextId = 0;
				}

				$segments[] = new Springbot_Util_Partition($nextId, $lastId);
				$lastId = $nextId;
				$blockCount++;
				if ($maxEntities && (($blockCount * $size) > $maxEntities)) break;
			} while ($nextId > $firstId);
		}

		return $segments;
	}

	private function _getLowestEntityId($collection, $idFieldName, $lastId, $size)
	{
		$collection->clear();
		$collection->getSelect()
			->reset(Zend_Db_Select::COLUMNS)
			->reset(Zend_Db_Select::WHERE)
			->reset(Zend_Db_Select::ORDER)
			->columns("$idFieldName")
			->order("{$idFieldName} DESC")
			->limit(1, $size);

		$collection->addFieldToFilter($idFieldName, array(
			'lt' => $lastId
		));

		$result = $collection->getFirstItem();
		if ($result) {
			return $result[$idFieldName];
		}
		else {
			return 0;
		}
	}


	public static function getSampleSize()
	{
		if ($sampleSize = Mage::getStoreConfig('springbot/config/sample_size')) {
			return $sampleSize;
		}
		else {
			return null;
		}
	}


	public static function getReverseSample()
	{
		if ($reverseSample = Mage::getStoreConfig('springbot/config/reverse_sample')) {
			return $reverseSample;
		}
		else {
			return false;
		}
	}

	/**
	 * Forecast collection count
	 *
	 * @param Varien_Data_Collection $collection
	 * @param int $storeId
	 * @param string $label
	 * @param int $harvestId
	 */
	public function forecast($collection, $storeId, $label, $harvestId = null)
	{
		try {
			$size = $collection->getSize();
			$message = "{$size} {$label} will be harvested from store : {$storeId}/{$this->getSpringbotStoreId($storeId)}";

			Springbot_Log::harvest($message, true, $storeId);

			if(!is_null($harvestId)) {
				$this->reportHarvestCount(array(
					'store_id' => $this->getSpringbotStoreId($storeId),
					'type' => $label,
					'estimate' => $size,
				), $harvestId);
			}

		} catch (Exception $e) {
			Springbot_Log::error($e);
			Springbot_Log::harvest("Unknown quantity of {$label} to harvest!");
		}
	}

	/**
	 * Helper method to delete remote objects
	 *
	 * @param array $post
	 * @param string $method
	 */
	public function deleteRemote(array $post, $method)
	{
		$serialized = json_encode($post);
		$file = Mage::getModel('combine/file_io');
		$file->write(sha1($serialized) . '.json', $serialized);

		Springbot_Boss::scheduleJob(
			'post:json',
			array(
				'n' => $file->getBaseFilename(),
				'm' => $method,
			), 0, 'listener'
		);
	}

	public function reportHarvestCount($params, $id = null)
	{
		Springbot_Log::debug("Reporting harvest count for store_id => $id");

		$api = Mage::getModel('combine/api');
		$payload = $api->wrap('harvest_segments', array($params));
		$id = is_null($id) ? $this->getHarvestId() : $id;

		if(!is_null($id)) {
			return $api->put("harvests/{$id}", $payload);
		}
	}


	public function getStoreUrl($storeId)
	{
		$store = Mage::app()->getStore($storeId);
		$url = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

		if($store->getStoreInUrl()) {
			$url .= $store->getCode();
		}

		return preg_replace('/\/$/', '', $url);
	}

	public function getSpringbotStoreId($id)
	{
		$storeIDIndex = 'store_id_' . $id;
		$botId = Mage::getStoreConfig('springbot/config/' . $storeIDIndex);

		if(!isset($botId)) {
			Springbot_Log::debug("Tried to find config for key : $storeIDIndex");
		}
		return $botId;
	}

	public function getStoresToHarvest()
	{
		$_stores = array();
		foreach (Mage::app()->getWebsites() as $website) {
			foreach ($website->getGroups() as $group) {
				foreach ($group->getStores() as $store) {
					if ($this->doHarvestStore($store->getStoreId())) {
						$_stores[$store->getStoreId()] = $store;
					}
				}
			}
		}
		ksort($_stores, SORT_NUMERIC);
		return $_stores;
	}

	public function doHarvestStore($storeId)
	{
		$harvest = true;
		if(!$this->_rulesBuilt) {
			$this->_buildStoreRules();
		}
		if(isset($this->_defines)) {
			$harvest = (in_array($storeId, $this->_defines));
		}
		if(isset($this->_ignores)) {
			$harvest = !(in_array($storeId, $this->_ignores));
		}
		return $harvest;
	}

	public function isHarvestRunning()
	{
		$jobs = Mage::getModel('combine/cron_queue')->getCollection();

		$jobs->addFieldToFilter('queue', array('neq' => 'listener'))
			->addFieldToFilter('attempts', 0);

		if($jobs->getSize() > 0) {
			return true;
		} else {
			return false;
		}
	}

	public function truncateEngineLogs()
	{
		@file_put_contents(Mage::getBaseDir('log') . DS . Springbot_Log::LOGFILE, '');
		@file_put_contents(Mage::getBaseDir('log') . DS . Springbot_Log::ERRFILE, '');
	}

	public function getHarvestId()
	{
		return $this->_harvestId;
	}

	public function setHarvestId($id)
	{
		$this->_harvestId = $id;
		return $this;
	}

	protected function _buildStoreRules()
	{
		$ignores = Mage::getStoreConfig('springbot/config/ignore_store_list');
		if(!empty($ignores)) {
			Springbot_Log::harvest('Ignore stores : ' . $ignores);
			$this->_ignores =explode(',', $ignores);
		}

		$defines = Mage::getStoreConfig('springbot/config/define_store_list');
		if(!empty($defines)) {
			Springbot_Log::harvest('Explicitly defined stores : ' . $defines);
			$this->_defines = explode(',', $defines);
		}

		$this->_rulesBuilt = true;
	}

	protected function getSegmentSize()
	{
		$size = Mage::getStoreConfig('springbot/config/segment_size');
		return $size ? $size : 100;
	}
}
