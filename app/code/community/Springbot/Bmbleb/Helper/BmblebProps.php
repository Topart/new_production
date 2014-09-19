<?php
/**
 * Created by JetBrains PhpStorm.
 * User: joereger
 * Date: 12/2/11
 * Time: 9:39 AM
 * To change this template use File | Settings | File Templates.
 */
class Socketware_Bmbleb_Helper_BmblebProps{



    public static function get($key) {
    	return Mage::getModel('bmbleb/bmblebprops')->getValueByKey($key);
    }

    public static function put($key, $value) {
    	return Mage::getModel('bmbleb/bmblebprops')->setValueByKey($key, $value);
    }






}