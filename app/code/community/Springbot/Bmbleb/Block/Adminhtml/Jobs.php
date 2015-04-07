<?php

class Springbot_Bmbleb_Block_Adminhtml_Jobs extends Mage_Adminhtml_Block_Widget_Grid_Container
{
	public function __construct()
	{
		$this->_controller = 'adminhtml_jobs';
		$this->_blockGroup = 'bmbleb';
		$this->_headerText = $this->__('Jobs');
		parent::__construct();
		$this->_removeButton('add');
	}
}
