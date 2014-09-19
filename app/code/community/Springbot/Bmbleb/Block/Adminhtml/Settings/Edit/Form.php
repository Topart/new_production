<?php
/**
 */
class Socketware_Bmbleb_Block_Adminhtml_Settings_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
        $form = new Varien_Data_Form();

        $fieldset = $form->addFieldset('bmbleb_password', array('legend'=>Mage::helper('bmbleb')->__('Change your springbot account password - leave this blank if you do not want to change it.')));

        $fieldset->addField('email', 'label', array(
              'label'     => Mage::helper('bmbleb')->__('Account Email Address'),
        	  'value'     => Mage::getStoreConfig('bmbleb/config/account_email',Mage::app()->getStore())
        ));
        
        $fieldset->addField('password', 'password', array(
              'label'     => Mage::helper('bmbleb')->__('Password'),
              //'class'     => 'required-entry',
              'required'  => false,
              'name'      => 'password',
       		  'note'	  => Mage::helper('bmbleb')->__('Passwords must be more than 6 characters long and cannot contain spaces or special characters')
         ));

        $fieldset->addField('passwordverify', 'password', array(
            'label'     => Mage::helper('bmbleb')->__('Confirm Password'),
            //'class'     => 'required-entry',
            'required'  => false,
            'name'      => 'passwordverify',
        ));
       
      
      $form->setMethod('post');
      $form->setUseContainer(true);
      $form->setId('edit_form');
      $form->setName('edit_form');
      $form->setAction($this->getUrl('*/*/post'));
      
      $this->setForm($form);


  }
}