<?php
/**
 * Created by PhpStorm.
 * User: ikis1520
 * Date: 29/06/15
 * Time: 0:50
 */
class Onetree_Seo_Model_Resource_Tester extends Mage_Core_Model_Resource_Db_Abstract
{

    protected function _construct()
    {
        $this->_init('seo/subterm', 'subterm_id');
    }

}