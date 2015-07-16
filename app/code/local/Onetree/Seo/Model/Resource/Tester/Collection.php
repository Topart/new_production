<?php
/**
 * Created by PhpStorm.
 * User: ikis1520
 * Date: 29/06/15
 * Time: 0:44
 */
class Onetree_Seo_Model_Resource_Tester_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{

    protected function _construct()
    {
        $this->_init('seo/tester');
    }

}