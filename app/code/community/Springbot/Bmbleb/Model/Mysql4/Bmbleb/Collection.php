<?php

class Socketware_Bmbleb_Model_Mysql4_Bmbleb_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('bmbleb/bmbleb');
    }
}