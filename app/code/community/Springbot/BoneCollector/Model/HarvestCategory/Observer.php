<?php

class Springbot_BoneCollector_Model_HarvestCategory_Observer extends Springbot_BoneCollector_Model_HarvestAbstract
{
	public function onCategorySaveAfter($observer)
	{
		try {
			$this->_initObserver($observer);
			$categoryId = $observer->getEvent()->getCategory()->getEntityId();
			if (!empty($categoryId)) {
				Springbot_Boss::scheduleJob('post:category', array('i' => $categoryId), Springbot_Services::LISTENER, 'listener');
			}
		}
		catch (Exception $e) {
			Springbot_Log::error($e);
		}
	}

	public function onCategoryDeleteAfter($observer)
	{
		try {
			$category = $observer->getEvent()->getCategory();
			$this->_initObserver($observer);
			foreach (Mage::helper('combine/harvest')->mapStoreIds($category) as $mapped) {
				$deleted = array(
					'store_id' => $mapped->getStoreId(),
					'cat_id' => $category->getEntityId(),
					'is_deleted' => true,
				);
				Mage::helper('combine/harvest')->deleteRemote($deleted, 'categories');
			}

		}
		catch (Exception $e) {
			Springbot_Log::error($e);
		}
	}
}
