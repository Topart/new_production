<?php
/**
 * Created by JetBrains PhpStorm.
 * User: joereger
 * Date: 12/10/11
 * Time: 4:31 PM
 * To change this template use File | Settings | File Templates.
 */
class Springbot_Bmbleb_Block_Adminhtml_Bmbleb_Register_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
  
  		$guid = Mage::getStoreConfig('bmbleb/config/store_guid',Mage::app()->getStore());
    	if (empty($guid) || $guid == '0' || $guid == ''){
    		$charid = strtoupper(md5(uniqid(rand(), true)));
            $guid = substr($charid, 0, 8).'-'
                   .substr($charid, 8, 4).'-'
                   .substr($charid,12, 4).'-'
                   .substr($charid,16, 4).'-'
                   .substr($charid,20,12);
			$config = new Mage_Core_Model_Config();
			$config->saveConfig('bmbleb/config/store_guid', $guid, 'default', 0);    		
    	}
        $form = new Varien_Data_Form();

        $fieldset = $form->addFieldset('bmbleb_form', array('legend'=>Mage::helper('bmbleb')->__('Register Now for a Springbot Account')));
       
	    $fieldset->addField('uname', 'text', array(
            'label'     => Mage::helper('bmbleb')->__('Your Name'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'uname',
        ));
        $fieldset->addField('email', 'text', array(
            'label'     => Mage::helper('bmbleb')->__('Email'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'email',
        ));

        $fieldset->addField('password', 'password', array(
              'label'     => Mage::helper('bmbleb')->__('Password'),
              'class'     => 'required-entry',
              'required'  => true,
              'name'      => 'password',
         ));

        $fieldset->addField('passwordverify', 'password', array(
            'label'     => Mage::helper('bmbleb')->__('Confirm Password'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'passwordverify',
        ));
 
        $fieldset->addField('storeguid', 'hidden', array(
            'value'      =>  $guid,
			'name'       => 'storeguid'
        ));		

/*
      $form->setMethod('post');
      $form->setUseContainer(true);
      $form->setId('register_form');
      $form->setName('register_form');
      $form->setAction($this->getUrl('*/register/register'));

       $this->setForm($form);
*/

  }
}