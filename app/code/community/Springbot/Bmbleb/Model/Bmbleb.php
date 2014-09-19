<?php

class Socketware_Bmbleb_Model_Bmbleb extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('bmbleb/bmbleb');
    }
}