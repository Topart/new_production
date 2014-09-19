<?php
/**
 * Created by JetBrains PhpStorm.
 * User: joereger
 * Date: 12/09/11
 * Time: 2:56 PM
 * To change this template use File | Settings | File Templates.
 */
class Springbot_Bmbleb_Block_Adminhtml_Bmbleb_Register extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_blockGroup = 'bmbleb';
        $this->_controller = 'adminhtml_bmbleb';
        $this->_mode = 'register';
        
        $this->_removeButton('back');
		$this->_removeButton('reset');
        $this->_removeButton('save');
        
		$this->_addButton('register', array(
            'label'     => Mage::helper('bmbleb')->__('Register Now'),
            'onclick'   => 'register_form.submit();',
            //'class'     => 'go'	// could use class of 'save' for checkmark
        ), 0, 100, 'footer');		
		
		
    }

    public function getHeaderText()
    {
        return Mage::helper('bmbleb')->__('Register');
    }
}