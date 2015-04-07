<?php

class Springbot_Bmbleb_Block_Adminhtml_Help extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();
		$this->setTemplate("bmbleb/help/index.phtml");
    }

}
