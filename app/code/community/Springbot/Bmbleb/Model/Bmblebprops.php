<?php

class Socketware_Bmbleb_Model_Bmblebprops extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('bmbleb/bmblebprops');
    }
    
    public function getValueByKey($key) {
        $props = Mage::getModel('bmbleb/bmblebprops')->getCollection();
        foreach($props as $prop){
            if ($prop->getKey() == $key){
                return $prop->getValue();
            }
        }

        return null;
    }
    
    public function setValueByKey($key, $value) {
        //Delete any value for key currently present
        $props = Mage::getModel('bmbleb/bmblebprops')->getCollection();
        foreach($props as $prop){
            if ($prop->getKey() == $key){
                $prop->delete();
            }
        }
        //Insert key/value pair
        $prop = Mage::getModel('bmbleb/bmblebprops');
        $prop->setKey($key);
        $prop->setValue($value);
        return $prop->save();
    }
    
    
}