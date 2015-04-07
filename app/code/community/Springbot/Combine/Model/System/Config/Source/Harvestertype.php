<?php

class Springbot_Combine_Model_System_Config_Source_Harvestertype
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'cron', 'label'=>Mage::helper('combine')->__('Cron')),
            array('value' => 'worker', 'label'=>Mage::helper('combine')->__('Worker')),
            array('value' => 'prattler', 'label'=>Mage::helper('combine')->__('Prattler')),
        );
    }
}
