<?php
/**
 * Created by PhpStorm.
 * User: diegopalda
 * Date: 02/04/15
 * Time: 11:29 AM
 */

class Onetree_ShopByArtist_IndexController extends Mage_Core_Controller_Front_Action{

    public function indexAction(){
        $this->loadLayout();
        $this->renderLayout();
    }
}