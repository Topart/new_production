<?php
/**
 * Created by JetBrains PhpStorm.
 * User: joereger
 * Date: 1/6/12
 * Time: 9:39 AM
 * To change this template use File | Settings | File Templates.
 */
class Socketware_Bmbleb_Helper_GetTermsOfService
{
  

	
    public static function getTermsOfService(){
    	$tos = '';
		try {
    	
	        $payload = array();
	        $payload['storeguid'] = Mage::helper('bmbleb/Sync')->getStoreGuid();
	        
	
	        $apiResponse = Mage::helper('bmbleb/ApiCall')->call("gettermsofservice", $payload);
	        
	        if ($apiResponse->getResponsecode() == "200"){
	            //Parse the response
	            //Note that JSON.php is an edited version (see bottom of file)
	            require_once('JSON.php');      // JSON parser
	            require_once('jsonpath-0.8.1.php');  // JSONPath evaluator
	            $parser = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
	            $o = $parser->decode($apiResponse->getFullresponse());
	            $tos = $o['termsofservice'];
	        } elseif ($apiResponse->getResponsecode() == "400"){
	        	// return the error message instead of using flash system
	            $tos = Mage::helper('bmbleb')->__('We\'re sorry, there\'s been an error loading the terms of service: ' . $apiResponse->getMessage());
	        } else {
	        	// return the error message instead of using flash system
	        	$tos = Mage::helper('bmbleb')->__('We\'re sorry, there\'s been an error loading the terms of service.');
	        }
	        // end try/catch
		}
		catch (Mage_Core_Exception $e) {
			Mage::log("Error loading alerts: Mage_Core_Exception " . $e->getMessage());
        	// return the error message instead of using flash system
        	$tos = Mage::helper('bmbleb')->__('There\'s been an error loading the terms of service.');
		}
		catch (Exception $e) {
			Mage::log("Error loading alerts: Exception " . $e->getMessage());
        	// return the error message instead of using flash system
        	$tos = Mage::helper('bmbleb')->__('There was an error loading the terms of service.');
		}
	    return $tos;

    }
}