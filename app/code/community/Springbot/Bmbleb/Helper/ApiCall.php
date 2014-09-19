<?php
class Springbot_Bmbleb_Helper_ApiCall
{
    private function getKey(){
    	return Mage::getStoreConfig('bmbleb/config/encrypt_key',Mage::app()->getStore());
    }
    private function getAPIHost(){
    	return Mage::getStoreConfig('bmbleb/config/api_url',Mage::app()->getStore());
    }

    public function call($APIMethod, $payloadAsArray)
    {
        try {
            $rawData='';

			$uri = $this->fetchConfigVariable('api_url','https://api.springbot.com/').'api/registration';
            if (isset($payloadAsArray['uname'])) {
			    $rawData='{'
			         . '"name":"'                 .$payloadAsArray['uname'].'",'
			         . '"email":"'                .$payloadAsArray['registeremail'].'",'
		 	         . '"password":"'             .$payloadAsArray['registerpassword'].'",'
				     . '"password_confirmation":"'.$payloadAsArray['verifypassword'].'"'
					 .'}';
 			}
            $client = new Varien_Http_Client($uri);
            $client->setRawData($rawData);
            $req 						    = $client->request('POST');

			$begJson						=strpos($req,'{');
			$endJson						=strpos($req,'}',$begJson);
			$jBuf							=substr($req,$begJson,($endJson-$begJson-1));
	    	$response						=json_decode($jBuf,true);
      		if ($response['status']=='error') { 
			    $errorCode					='999';
			} else {
			    $errorCode					='200';
			}
			
            try{
              //Populate ApiResponse object
                $apiResponse = Mage::helper('bmbleb/ApiResponse');
                $apiResponse->setFullresponse($req);
                $apiResponse->setResponsecode($errorCode);
                $apiResponse->setResponsecodedescription($response['status']);
                $apiResponse->setMessage($response['message']);
	            return $apiResponse;
				
            }catch (Exception $e) {
                Mage::log($e);
            }

        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            Mage::log($e->getMessage());
        }

        $apiResponse = Mage::helper('bmbleb/ApiResponse');
        $apiResponse->setFullresponse('');
        $apiResponse->setResponsecode('500');
        $apiResponse->setResponsecodedescription('Server Error');
        $apiResponse->setMessage('An unknown error.');
		
        return $apiResponse;
    }
 
    private function getEncrypt($sStr, $sKey = "")
    {
        if ($sKey==null || $sKey==""){$sKey = self::getKey();}
        $sStr = self::pkcs5_pad($sStr, 16);

        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

        return base64_encode(
            mcrypt_encrypt(
                MCRYPT_RIJNDAEL_128,
                $sKey,
                $sStr,
                MCRYPT_MODE_ECB,
                $iv
            )
        );
    }

    private function getDecrypt($sStr, $sKey = "")
    {
        if ($sKey==null || $sKey==""){$sKey = self::getKey();}

        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

        return mcrypt_decrypt(
            MCRYPT_RIJNDAEL_128,
            $sKey,
            base64_decode($sStr),
            MCRYPT_MODE_ECB,
            $iv
        );
    }

    private function pkcs5_pad($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
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
