<?php

class Springbot_BoneCollector_Model_HarvestSubscriber_Observer extends Springbot_BoneCollector_Model_HarvestAbstract
{
	public function onSubscriberSaveAfter($observer)
	{
		try {
			$this->_initObserver($observer);
			$subscriberId = $observer->getEvent()->getSubscriber()->getId();

			Springbot_Boss::scheduleJob(
				'post:subscriber',
				array('i' => $subscriberId),
				Springbot_Services::LISTENER,
				'listener'
			);

		}
		catch (Exception $e) {
			Springbot_Log::error($e);
		}
	}

	public function onSubscriberDeleteBefore($observer)
	{
		try {
			// Runs blocking in session to guarantee record existence
			$this->_initObserver($observer);
			Mage::getModel('Springbot_Services_Post_Subscriber')->setData(array(
				'start_id' => $observer->getEvent()->getSubscriber()->getId(),
				'delete' => true,
			))->run();
		}
		catch (Exception $e) {
			Springbot_Log::error($e);
		}
	}
}

