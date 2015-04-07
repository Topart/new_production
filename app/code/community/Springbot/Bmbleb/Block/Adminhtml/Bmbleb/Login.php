<?php
/**
 * Created by JetBrains PhpStorm.
 * User: joereger
 * Date: 12/10/11
 * Time: 2:02 PM
 * To change this template use File | Settings | File Templates.
 */
class Springbot_Bmbleb_Block_Adminhtml_Bmbleb_Login extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_blockGroup = 'bmbleb';
        $this->_controller = 'adminhtml_bmbleb';
        $this->_mode = 'login';
        
        $this->_removeButton('back');
		$this->_removeButton('reset');
		$this->_removeButton('save');

		$this->_addButton('login', array(
            'label'     => Mage::helper('bmbleb')->__('Login'),
            'onclick'   => 'login_form.submit();',
        ), 0, 100, 'footer');		
		
    }

	/*
	NOTE: the issue with submitting is being caused because of this html
		  editForm = new varienForm('edit_form', '');
		  this is also the reason js validation is not working
	*/
    public function getHeaderText()
    {
    	return Mage::helper('bmbleb')->__('Log In');
    }
}