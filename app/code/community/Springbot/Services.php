<?php

/**
 * Class: Springbot_Services
 *
 * @author Springbot Magento Integration Team <magento@springbot.com>
 * @version 1.4.0.0
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @abstract
 */
abstract class Springbot_Services extends Varien_Object
{
	const HARVEST = 0;
	const PARTITION = 1;
	const SEGMENT = 2;
	const CATEGORY = 3;
	const LISTENER = 5;
	const FAILED = 8;

	protected $_type = 'items';
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
			//throw new Exception($this->_humanize($key) . ' required for harvest!');
			return null;
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
			return Mage::app()->getStore()->getStoreId();
		}
		return 0;
	}

	public function getSpringbotStoreId()
	{
		return Mage::helper('combine/harvest')
			->getSpringbotStoreId($this->getStoreId());
	}

	public function getStartId()
	{
		$value = parent::getData('start_id');
		return isset($value) ? $value : 0;
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

	public function getSegmentMin($harvester)
	{
		return $harvester->getSegmentMin();
	}

	public function getSegmentMax($harvester)
	{
		return $harvester->getSegmentMax();
	}

	public function getRuntime()
	{
		return number_format(microtime(true) - $this->_startTime, 3, '.', '');
	}

	public function doFinally() {

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
