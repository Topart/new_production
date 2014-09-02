<?php
/**
 * Created by JetBrains PhpStorm.
 * User: joereger
 * Date: 1/6/12
 * Time: 9:39 AM
 * To change this template use File | Settings | File Templates.
 */
class Socketware_Bmbleb_Helper_ChangePassword
{

	
    public static function ChangePassword($newpassword){
		try {
    	
	        $payload = array();
	        $payload['newpassword'] = $newpassword;
	        $payload['storeguid'] = Mage::helper('bmbleb/Sync')->getStoreGuid();
	        
	
	        $apiResponse = Mage::helper('bmbleb/ApiCall')->call("changepassword", $payload);
	        Mage::log("ChangePassword responsecode=" . $apiResponse->getResponsecode());
			Mage::log("changepassword json: " . $apiResponse->getFullresponse());
	        
	        if ($apiResponse->getResponsecode() == "200"){
	            Mage::log("Starting to parse changepassword json");
	            //Parse the response
	            //Note that JSON.php is an edited version (see bottom of file)
	            require_once('JSON.php');      // JSON parser
	            require_once('jsonpath-0.8.1.php');  // JSONPath evaluator
	            $parser = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
	            $o = $parser->decode($apiResponse->getFullresponse());
				return true;
	
	        } elseif ($apiResponse->getResponsecode() == "400"){
	        	return $apiResponse->getMessage();
	            //Mage::getSingleton('adminhtml/session')->addError(Mage::helper('bmbleb')->__('We\'re sorry, there\'s been an error: ' . $apiResponse->getMessage()));
	        } else {
	        	return Mage::helper('bmbleb')->__('There was an error updating your password');
	            //Mage::getSingleton('adminhtml/session')->addError(Mage::helper('bmbleb')->__('We\'re sorry, there\'s been an error.'));
	        }
	        // end try/catch
		}
		catch (Mage_Core_Exception $e) {
			Mage::log("Error changing password: Mage_Core_Exception " . $e->getMessage());
			return $e->getMessage();
		}
		catch (Exception $e) {
			Mage::log("Error changing password: Exception " . $e->getMessage());
			return $e->getMessage();
		}
		// if passes through to here there was an error
    	return Mage::helper('bmbleb')->__('Unspecified error');
    }
}