<?php
class Springbot_Services_Cmd_Harvest extends Springbot_Services
{
	const SEGMENT_SIZE = 2000;

	protected $_harvestId;

	// Defines order which entities are harvested during a full harvest
	protected static $_classes = array(
		'categories',
		'attributeSets',
		'customerAttributeSets',
		'products',
		'purchases',
		'customers',
		'guests',
		'subscribers',
		'coupons',
		'rules',
		//'carts',
	);


	public static function getClasses()
	{
		// 1.3 does not have salesrule module
		if(!mageFindClassFile('Mage_SalesRule_Model_Coupons')) {
			self::$_classes = array_merge(array_diff(
				self::$_classes, array('coupons', 'rules')
			));
		}
		return self::$_classes;
	}

	protected function _init()
	{
		$service = new Springbot_Services_Store_Register;

		// Init all stores upfront
		foreach ($this->getHelper()->getStoresToHarvest() as $store) {
			$service->setStoreId($store->getStoreId())->run();
		}

		// Have to clear cache in parent thread after config set
		Mage::getConfig()->cleanCache();
		Springbot_Log::debug(Mage::getStoreConfig('springbot'));
	}

	public function run()
	{
		Springbot_Log::debug(__METHOD__);

		if ($this->getIsResume()) {
			$this->_resumeHarvest();
		}
		else if ($this->hasClass() && $this->hasRange()) {
			$this->_harvest($this->getClass(), $this->getStoreId());
		}
		else if ($this->hasClass()) {
			$this->_segmentHarvest($this->getClass(), $this->getStoreId());
		}
		else {
			$this->_fullHarvest();
		}
	}

	protected function _fullHarvest()
	{
		if($this->getHelper()->isHarvestRunning()) {
			throw new Exception('Harvest is running already!');
		}

		$this->_init();

		//Iterate all stores
		foreach ($this->getHelper()->getStoresToHarvest() as $store) {
			$this->_harvestId = Mage::helper('combine/harvest')->initRemoteHarvest($store->getStoreId());
			$this->_harvestStore($store, self::getClasses(), $this->_harvestId);
		}
	}

	protected function _resumeHarvest()
	{
		$harvestCursor = Mage::getStoreConfig('springbot/config/harvest_cursor');

		if (!$harvestCursor) {
			Springbot_Log::remote('Resume harvest command received, no valid harvest cursor found: Cursor value: ' . $harvestCursor);
		}
		else {
			list($lastClassCompleted, $partition, $storeId) = explode('|', $harvestCursor);
			foreach($this->getHelper()->getStoresToHarvest() as $store) {
				$this->_harvestId = Mage::helper('combine/harvest')->initRemoteHarvest($store->getStoreId());
				if ($store->getStoreId() == $storeId) {
					// Only harvest classes for this store that have not been fully or partially harvested yet
					$classesLeft = $this->_getClassesSubset($lastClassCompleted);
					$this->_partialHarvestClass($store, $lastClassCompleted, $partition, $this->_harvestId);
					$this->_harvestStore($store, $classesLeft, $this->_harvestId);
				}
				else if ($store->getStoreId() > $storeId) {
					// Harvest has not begun for this store so harvest all classes
					$this->_harvestStore($store, self::getClasses(), $this->_harvestId);
				}
			}
		}
	}

	protected function _partialHarvestClass(Mage_Core_Model_Store $store, $class, $partition, $harvestId)
	{
		Springbot_Log::debug("Partial harvest started {$store->getStoreId()} | $class | {$harvestId}");
		Springbot_Boss::scheduleJob(
			'cmd:harvest',
			array(
				's' => $store->getStoreId(),
				'c' => $class,
				'v' => $harvestId,
				'i' => $partition,
			),
			Springbot_Services::CATEGORY,
			'default',
			$store->getStoreId()
		);
	}

	protected function _harvestStore(Mage_Core_Model_Store $store, array $classes, $harvestId) {
		$this->_logStoreHeader($store);

		$forecastService = new Springbot_Services_Cmd_Forecast;
		$forecastService->forecastStore($store->getStoreId(), $this->_harvestId);

		foreach ($classes as $class) {
			Springbot_Boss::scheduleJob(
				'cmd:harvest',
				array(
					's' => $store->getStoreId(),
					'c' => $class,
					'v' => $harvestId,
				),
				Springbot_Services::CATEGORY,
				'default',
				$store->getStoreId()
			);
			Springbot_Boss::scheduleJob(
				'work:report',
				array(
					's' => $store->getStoreId(),
					'c' => $class,
					'v' => $harvestId,
				),
				Springbot_Services::CATEGORY,
				'default',
				$store->getStoreId()
			);
		}
		Springbot_Boss::scheduleJob(
			'store:finalize',
			array('s' => $store->getStoreId()),
			Springbot_Services::CATEGORY,
			'default',
			$store->getStoreId()
		);
	}

	/**
	 * Harvests a class segment for a specific store.  Possible flags are:
	 *
	 * -s store_id, required
	 * -i _start_:_stop_ will limit the collection to be partitioned - both sides are optional
	 * -v harvest_id
	 *
	 * This will forecast the collection to be harvested, and it will then be split up into
	 * smaller chunks, each of which will be scheduled.
	 *
	 * @param string $key
	 * @param int $storeId
	 */
	protected function _harvest($key, $storeId)
	{
		$count = 0;
		$keyUpper = ucwords($key);

		Springbot_Log::harvest("Harvesting {$keyUpper}");

		$collection = $this->_getCollection($keyUpper, $storeId);

		$scheduler = Mage::getModel('combine/cron_queue_batch');

		foreach(Mage::helper('combine/harvest')->partitionCollection($collection) as $partition) {
			$count++;
			$scheduler->schedule(
				"harvest:{$key}",
				array(
					's' => $storeId,
					'i' => (string) $partition,
					'c' => $key,
					'v' => $this->getHarvestId(),
				),
				Springbot_Services::PARTITION,
				'partition', // Partition queue
				$storeId
			);
			//Mage::getModel('core/config')->saveConfig('springbot/config/harvest_cursor', $key . '|' . $partition->fromStart() . '|' . $storeId);
		}
		$scheduler->insert();

		Springbot_Log::harvest("{$count} partitions created for {$keyUpper}");
	}

	protected function _segmentHarvest($key, $storeId)
	{
		$keyUpper = ucwords($key);
		$collection = $this->_getCollection($keyUpper, $storeId);

		Springbot_Log::harvest("Segmenting {$keyUpper}");
		$scheduler = Mage::getModel('combine/cron_queue_batch');

		$this->_reportHarvestStartTime($this->getHarvestId(), $storeId, $key);

		$this->getHelper()->forecast($collection, $storeId, $keyUpper, $this->getHarvestId());

		$count = 0;
		foreach(Mage::helper('combine/harvest')->partitionCollection($collection, self::SEGMENT_SIZE) as $partition) {
			$count++;
			$scheduler->schedule(
				"cmd:harvest",
				array(
					's' => $storeId,
					'c' => $key,
					'i' => (string) $partition,
					'v' => $this->getHarvestId(),
				),
				Springbot_Services::SEGMENT,
				'default',
				$storeId
			);
		}
		$scheduler->insert();
		Springbot_Log::harvest("{$count} segments created for {$key}");
	}

	private function _reportHarvestStartTime($harvestId, $storeId, $type)
	{
		$cronCount = Mage::getModel('combine/cron_count');

		// Create the cron count row for the entity if it doesn't exist already.
		$cronCount->increaseCount($storeId, $harvestId, $type, 0);

		$started = $cronCount->getEntityStartTime($storeId, $harvestId, $type);
		$params = array(
			'store_id' => $this->getHelper()->getSpringbotStoreId($storeId),
			'type' => $type,
			'started' => $started,
		);
		$api = Mage::getModel('combine/api');
		$payload = $api->wrap('harvest_segments', array($params));
		if (!is_null($harvestId)) {
			return $api->put("harvests/{$harvestId}", $payload);
		}
	}

	/**
	 * @return Varien_Db_Collection_Abstract
	 */
	protected function _getCollection($type, $storeId)
	{
		Springbot_Log::debug("Building collection $type for partition => {$this->getPartition()}");
		$harvestServiceClassName = 'Springbot_Services_Harvest_' . $type;
		$harvestServiceObject = new $harvestServiceClassName;
		return $harvestServiceObject->getCollection($storeId,  $this->getPartition());
	}


	public function getPartition()
	{
		return new Springbot_Util_Partition($this->getStartId(), $this->getStopId());
	}

	public function getHarvestId()
	{
		return isset($this->_harvestId) ? $this->_harvestId : $this->_data['harvest_id'];
	}

	public function getHelper()
	{
		return Mage::helper('combine/harvest');
	}

	/**
	 * When resuming a harvest, get all class types that have not been fully or partially harvested yet
	 *
	 * @param $lastClassCompleted
	 * @return array
	 */
	private function _getClassesSubset($lastClassCompleted) {
		$classesLeft = array();
		$foundLastClass = false;
		foreach (self::getClasses() as $class) {
			if ($foundLastClass) {
				$classesLeft[] = $class;
			}
			if ($class == $lastClassCompleted) {
				$foundLastClass = true;
			}
		}
		return $classesLeft;
	}

	private function _logStoreHeader(Mage_Core_Model_Store $store)
	{
		$helper = Mage::helper('combine/store')->setStore($store);

		Springbot_Log::printLine(true);
		Springbot_Log::harvest("Harvesting Store {$store->getUrl()} => {$store->getId()}/{$helper->getSpringbotStoreId()}", true);
		Springbot_Log::harvest("Harvest ID => {$this->_harvestId}\nGUID => {$helper->getGuid()}\nEmail => {$helper->getAccountEmail()}", true);
		Springbot_Log::printLine(true);
	}

}
