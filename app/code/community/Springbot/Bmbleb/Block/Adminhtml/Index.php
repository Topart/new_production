<?php

class Springbot_Bmbleb_Block_Adminhtml_Index extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();
		$this->setTemplate("bmbleb/index.phtml");
    }
    
    public function didSyncThisSession()
	{
    	return false;
    }
    
    public function autoLauchSync()
	{
    	return false;
    }
}
