<?php

class Springbot_Bmbleb_Block_Adminhtml_Tabs extends Mage_Adminhtml_Block_Template
{
    /**
     * Block constructor
     */
    public function __construct()
    {
        parent::__construct();
		$this->setTemplate("bmbleb/tabs.phtml");
    }
    
    public function isActive($controller = '', $action = '')
    {
    	return ($controller == $this->getRequest()->getControllerName() && ($action == '' || $action == $this->getRequest()->getActionName()));
    }
    
    // basic check for login status
    public function isLoggedIn()
    {
    	return Mage::helper('bmbleb/Account')->authorize('sync');
    }

	/**
	 * Uses PluginStatus helper to determine if major problem needs to be displayed globally
	 */
	public function useExtendedAdmin()
	{
		return (Mage::getStoreConfig('springbot/advanced/extended_config') == 1);
	}
}
