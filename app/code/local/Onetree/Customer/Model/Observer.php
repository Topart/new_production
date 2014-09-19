<?php

class Onetree_Customer_Model_Observer {

    public function setFirstLogin($observer) {
        Mage::getSingleton('customer/session')->setFirstTime(1);
        return $this;
    }

    public function IsFirstLogin($observer) {
        //if (!is_null(Mage::registry('customer_pro'))) {
        if (Mage::getSingleton('customer/session')->getFirstTime()) {
            $observer->getEvent()->getLayout()->getUpdate()->addHandle('customer_first_time');
            Mage::getSingleton('customer/session')->setData('first_time',0);
        }
    }

    public function setProCustomer($observer) {
        $params = Mage::app()->getFrontController()->getRequest()->getParams();
        
        if(isset($params["customer-type"])){
            if($params["customer-type"] === "pro"){
                try {
                    $customer = $observer->getCustomer();
                    // 5 is the "Pro" Group id
                    $customer->setData('group_id', 5); 
                } catch (Exception $e) {
                    Mage::log("customer_save_before observer failed: " . $e->getMessage());
                }
            }
        }
    }

}
