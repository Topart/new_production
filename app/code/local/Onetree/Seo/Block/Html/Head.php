<?php
/**
 * Created by PhpStorm.
 * User: ikis1520
 * Date: 5/07/15
 * Time: 20:38
 */
class Onetree_Seo_Block_Html_Head extends Mage_Page_Block_Html_Head
{
    protected function _construct()
    {
        $this->setTemplate('seo\template\page\html\head.phtml');
    }
}