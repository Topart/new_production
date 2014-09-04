<?php
/**
 */
class Springbot_Bmbleb_Block_Adminhtml_Settings extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
      $this->_blockGroup = 'bmbleb';
      $this->_controller = 'adminhtml_settings';
      $this->_headerText = Mage::helper('bmbleb')->__('Springbot Settings');
      
      parent::__construct();
      
      $this->_removeButton('reset');
      $this->_removeButton('back');
      
    }
    
    /**
     * Check permission for passed action
     *
     * @param string $action
     * @return bool
     */
    protected function _isAllowedAction($action)
    {
    	return Mage::getSingleton('admin/session')->isAllowed('bmbleb/adminhtml_settings/' . $action);
    }    
}