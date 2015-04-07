<?php
class Springbot_Bmbleb_Block_Adminhtml_Index_Messages extends Mage_Adminhtml_Block_Template
{
    /**
     * Block constructor
     */
    public function __construct()
    {
        parent::__construct();
		$this->setTemplate("bmbleb/index/messages.phtml");
    }

}
