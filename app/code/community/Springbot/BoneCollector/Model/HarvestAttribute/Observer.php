<?php

class Springbot_BoneCollector_Model_HarvestAttribute_Observer extends Springbot_BoneCollector_Model_HarvestAbstract
{
	public function onAdminAttributeSaveAfter($observer)
	{
		try {
			$this->_initObserver($observer);
			$attribute = $observer->getEvent()->getAttribute();
			if ($attribute->getIsUserDefined()) {
				if ($this->doSend($attribute, 'sb_eav_entity_attribute_obs_hash')) {
					Springbot_Log::debug("Attempting to post parent attribute sets");
					Springbot_Boss::scheduleJob(
						'post:attribute',
						array('i' => $attribute->getAttributeId()),
						Springbot_Services::LISTENER,
						'listener'
					);
				}
			}
			else {
				Springbot_Log::debug("Attribute is not user defined, skipping");
			}
		}
		catch (Exception $e) {
			Springbot_Log::error($e);
		}
	}

	public function onAdminAttributeSetSaveAfter($observer)
	{
		try {
			$attribute = $observer->getEvent()->getAttribute();
			$set = $this->_getAttributeSet($attribute->getAttributeSetId());
			if ($this->doSend($set, 'sb_eav_entity_attribute_set_obs_hash')) {
				$this->_initObserver($observer);
                Springbot_Boss::scheduleJob('post:attributeSet', array('i' => $attribute->getAttributeSetId()), Springbot_Services::LISTENER, 'listener');
			}
		}
		catch (Exception $e) {
			Springbot_Log::error($e);
		}
	}

	protected function _getAttributeSet($id)
	{
		$helper = Mage::helper('combine/attributes');

		// invalidate cache as attributes are added to set
		$attrIds = $helper->getAttributesBySet($id)->getAllIds();
		return $helper->getAttributeSetById($id)->setNestedAttributeIds($attrIds);
	}
}
