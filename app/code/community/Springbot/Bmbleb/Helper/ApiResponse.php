<?php
class Springbot_Bmbleb_Helper_ApiResponse
{
    private $responsecode;
    private $responsecodedescription;
    private $message;
    private $fullresponse;
    private $customers;
	private $usageLimits;

    public function setMessage($message) {
        $this->message = $message;
    }

    public function getMessage() {
        return $this->message;
    }

    public function setResponsecode($responsecode) {
        $this->responsecode = $responsecode;
    }

    public function getResponsecode() {
        return $this->responsecode;
    }

    public function setResponsecodedescription($responsecodedescription) {
        $this->responsecodedescription = $responsecodedescription;
    }

    public function getResponsecodedescription() {
        return $this->responsecodedescription;
    }

    public function setFullresponse($fullresponse) {
        $this->fullresponse = $fullresponse;
    }

    public function getFullresponse() {
        return $this->fullresponse;
    }

    public function setCustomers($value) {
        $this->customers = $value;
    }

    public function getCustomers() {
        return $this->customers;
    }

    public function setUsageLimits($value) {
    	$this->usageLimits = $value;
    }
    
    public function getUsageLimits() {
    	return $this->usageLimits;
    }
    
}