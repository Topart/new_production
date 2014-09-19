<?php

abstract class Springbot_Services_Abstract extends Varien_Object
{
	protected $_type = 'items';
	protected $_harvester;
	protected $_startTime;

	protected function _construct()
	{
		$this->_startTime = microtime(true);
	}

	abstract public function run();

	public function getData($key = '', $index = NULL)
	{
        $val = parent::getData($key);

		if(!(isset($val) || is_array($val))) {
			throw new Exception($this->_humanize($key) . ' required for harvest!');
		} else {
			return $val;
		}
	}

	public function getHarvestId()
	{
		return parent::getData('harvest_id');
	}

	public function hasRange()
	{
		return isset($this->_data['start_id']) || isset($this->_data['stop_id']);
	}

	public function getStoreId()
	{
		if($storeId = parent::getData('store_id')) {
			return $storeId;
		} else {
			return 0;
		}
	}

	public function getStartId()
	{
		return parent::getData('start_id');
	}

	public function getStopId()
	{
		return parent::getData('stop_id');
	}

	public function getFailedStartId()
	{
		return parent::getData('failed_start_id');
	}

	public function getFailedStopId()
	{
		return parent::getData('failed_stop_id');
	}

	public function getIsResume()
	{
		return isset($this->_data['resume']);
	}

	public function getLastFailedPartition()
	{
		return isset($this->_data['failed_partition']);
	}

	public function getForce()
	{
		return isset($this->_data['force']) && $this->_data['force'] === true;
	}

	public function getProcessedCount()
	{
		return $this->getHarvester()->getProcessedCount();
	}

	public function getHarvesterName()
	{
		return $this->getHarvester()->getHarvesterName();
	}

	public function getHarvester()
	{
		return $this->_harvester;
	}

	public function getSegmentMin()
	{
		return $this->getHarvester()->getSegmentMin();
	}

	public function getSegmentMax()
	{
		return $this->getHarvester()->getSegmentMax();
	}

	public function getType()
	{
		return ucwords($this->_type);
	}

	public function getRuntime()
	{
		return number_format(microtime(true) - $this->_startTime, 3, '.', '');
	}

	protected function _humanize($var)
	{
		return ucfirst(preg_replace('/\_/', ' ', $var));
	}

	protected function _getStatus()
	{
		return Mage::getSingleton('combine/cron_manager_status');
	}
}
