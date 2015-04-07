<?php

class Springbot_Bmbleb_Block_Adminhtml_Connected extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();
		$this->setTemplate("bmbleb/status.phtml");
    }
}
