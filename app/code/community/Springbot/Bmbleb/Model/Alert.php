<?php

class Socketware_Bmbleb_Model_Alert extends Mage_Core_Model_Abstract
{
    
    public function _construct()
    {
        parent::_construct();
        $this->_init('bmbleb/alert');
    }
    
	/* 
	 * Returns the next Alert that should be completed using the following logic:
	 * exclude all completed ones
	 * AND return new ones before skipped ones
	 * AND return higher priority ones first
	 * AND return the most recently updated ones first
	 */ 
    public function getNextToDo()
	{
		$collection = Mage::getModel('bmbleb/alert')->getCollection()
			->addFieldToFilter('status', array('neq' => Socketware_Bmbleb_Model_Alert_Status::COMPLETED));
		$collection->addOrder('status', 'asc')
			->addOrder('priority', 'desc')
			->addOrder('updated_at', 'desc');
    	return $collection->getFirstItem();
	}
	
	public function getStatusCounts()
	{
		$collection = Mage::getModel('bmbleb/alert')->getCollection()
			->removeAllFieldsFromSelect()
			->removeFieldFromSelect($this->getIdFieldName())
			->addFieldToSelect('status')
			->addExpressionFieldToSelect('total', 'COUNT({{status}})', 'status');
		// NOTE: had to add the group by clause separately and to the select itself
        $collection->getSelect()->group('status');
        
        $stats = array(
          'new' => 0,
          'skipped' => 0,
          'completed' => 0,
          'total' => 0
        );
		foreach ($collection as $model){
          switch ($model->getStatus()){
            case Socketware_Bmbleb_Model_Alert_Status::UNOPENED:
              $stats['new'] = $model->getTotal();
              break;
            case Socketware_Bmbleb_Model_Alert_Status::SKIPPED:
              $stats['skipped'] = $model->getTotal();
              break;
            case Socketware_Bmbleb_Model_Alert_Status::COMPLETED:
              $stats['completed'] = $model->getTotal();
              break;
          }
          $stats['total'] += $model->getTotal();
		}
        return $stats;
	}
	
    
}