<?php
/**
 * Created by JetBrains PhpStorm.
 * User: joereger
 * Date: 12/10/11
 * Time: 6:45 AM
 * To change this template use File | Settings | File Templates.
 */
class Springbot_Bmbleb_Block_Adminhtml_Bmbleb_Login_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
        $form = new Varien_Data_Form();


        $fieldset = $form->addFieldset('bmbleb_form', array('legend'=>Mage::helper('bmbleb')->__('Already Registered at www.springbot.com?')));

        $fieldset->addField('email', 'text', array(
              'label'     => Mage::helper('bmbleb')->__('Email'),
              'class'     => 'required-entry',
              'required'  => true,
              'name'      => 'email'
        ));

        $fieldset->addField('password', 'password', array(
              'label'     => Mage::helper('bmbleb')->__('Password'),
              'class'     => 'required-entry',
              'required'  => true,
              'name'      => 'password',
         ));

		$fieldset->addField('link', 'note', array(
			'label' => '',
			'text' => '<a href="http://www.springbot.com">Need a Springbot Account? Click Here</a>',
		));

	    // Hide submit button so that we can press enter to submit
	  	$submitButton = new Varien_Data_Form_Element_Submit(array('style' => 'position: absolute; left: -9999px; width: 1px; height: 1px;'));
	    $fieldset->addElement($submitButton);

       $form->setMethod('post');
       $form->setUseContainer(true);
       $form->setId('login_form');
       $form->setName('login_form');
       $form->setAction($this->getUrl('*/login/login'));
       $this->logPageVisit();
       $this->setForm($form);
    }
    private function logPageVisit()
	{
		$this->configVars		   =array();
		$springbotStoreID		   ='';

		$storeID				   =Mage::app()->getStore()->getStoreId();
		$storeURL				   =Mage::getStoreConfig('web/unsecure/base_url',$storeID);
		if (empty($url)) {  $url=Mage::getBaseDir(); }

		$varIndex				   ='store_id_'.$storeID;
        $this->configVars = Mage::getStoreConfig('springbot/config',$storeID);
		if (isset($this->configVars[$varIndex])) {
		   $springbotStoreID	   =$this->configVars[$varIndex];
		}
		$eventDatetime			   =date("Y-m-d H:i:s ".'-0500');
        $pri					   ='1';
		$msg					   ='User is viewing Sprinnbgot Login Form from '.$storeURL;
        $url = $this->fetchConfigVariable('api_url','https://api.springbot.com/').'api/logs';
        $rawJSON='{"logs" :{"'.$springbotStoreID.'":{"store_id":"'.$springbotStoreID.'",'
                        .'"event_time":"' .$eventDatetime.'",'
                        .'"store_url":"'  .$storeURL.'",'
						.'"remote_addr":"",'
                        .'"priority":"'   .$pri.'",'
                        .'"description":"'.$msg.'"'
    					.'}}}';

		try {
			$client = new Varien_Http_Client($url);
			$client->setRawData($rawJSON);
			$req = $client->request('POST');
		} catch (Exception $e) {
			Mage::log('Remote Springbot service unavailable!');
			Mage::logException($e);
		}
	}
    private function fetchConfigVariable($varName,$default_value='')
	{
	    if (isset($this->configVars[$varName])) {
		 	  	$rtnValue  = $this->configVars[$varName];
		} else {
		   		$rtnValue = $default_value;
		}
		return $rtnValue;
 }
}
