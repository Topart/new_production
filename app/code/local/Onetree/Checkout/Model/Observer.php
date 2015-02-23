<?php

class Onetree_Checkout_Model_Observer {

    public function addtocart($observer) {
        Mage::getModel('core/session')->setProductAddedToCart(true);
    }

}
