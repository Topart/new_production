<?php

class Springbot_Services_Tasks_DeliverEventLog extends Springbot_Services
{
	const EVENT_MAX = 200;

	public function run()
	{
		$ssid = $this->getSpringbotStoreId();
		$method = "stores/{$ssid}/products/actions/create";
		$eventItems = array();
		try{
			$this->_lockEvents();
			foreach($this->_getLockedEvents() as $event) {
				$eventItems[] = $event->toAction();
			}
			if ($eventItems) {
				$result = Mage::getModel('combine/api')->call($method, json_encode($eventItems));
			}
			$this->_removeEvents();
			$successful = true;
		} catch (Exception $e) {
			// We can capture this here and keep if from bubbling up.
			// This api call will fail and get recreated on the next check in
			Springbot_Log::error($e);
			$successful = false;
		}
		$this->_releaseLocks();
		return $successful;
	}

	private function _removeEvents()
	{
		$this->_getEventsResource()->removeEvents(getmypid());
	}

	private function _getLockedEvents()
	{
		return $this->_getEventsCollection()
			->getLockedEvents($this->getStoreId(), getmypid());
	}

	private function _lockEvents()
	{
		$this->_getEventsResource()
			->lockEvents(getmypid(), $this->getStoreId(), self::EVENT_MAX);
	}

	private function _releaseLocks()
	{
		$this->_getEventsResource()->releaseLocksForPid(getmypid());
	}

	private function _getEventsCollection()
	{
		return Mage::getModel('combine/action')->getCollection();
	}

	private function _getEventsResource()
	{
		return Mage::getResourceModel('combine/action');
	}
}
