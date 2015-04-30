<?php

class Socketware_Bmbleb_Model_Sync extends Mage_Core_Model_Abstract
{
    const STATUS_STARTED = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_PARTIALLY_COMPLETE = 2;
    const STATUS_COMPLETED = 3;
    
    public function _construct()
    {
        parent::_construct();
        $this->_init('bmbleb/sync');
    }
    
    public function getParsedDetails()
    {
    	if ($this->getDetails() != ''){
    		return Zend_Json::decode($this->getDetails());
    	}
    	return array();
    }
    
    public function setParsedDetails($dataArray = array())
    {
    	$this->setDetails(Zend_Json::encode($dataArray));
    }
}