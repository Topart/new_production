<?php

/**
 * Created by PhpStorm.
 * User: ikis1520
 * Date: 1/07/15
 * Time: 23:07
 */
class Onetree_Seo_Block_Adminhtml_Setting extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_controller = 'adminhtml_setting'; # this is the common prefix in the second part of the grouped class name, i.e. whatever/(this_bit)
        $this->_blockGroup = 'onetree_seo'; # the first part of the grouped class name, i.e. (some_module)/whatever
        $this->_headerText = Mage::helper('onetree_seo')->__('Settings'); # sets the name in the header

        //$this->_addButtonLabel = Mage::helper('onetree_seo')->__('Add New Setting'); # sets the text for the add button

        parent::__construct(); # for grid containers, parent constructor must be called last - not good design
        $this->_removeButton('add');#remove add button of grid
    }

    /**
     * Header CSS class
     *
     * Used to set the icon next to the header text, not at all needed but a
     * nice touch. Look at all the headers to see the available icons, or make
     * your own by omitting this and making a CSS rule for .head-adminhtml-thing
     *
     * @return string The CSS class
     */
    public function getHeaderCssClass()
    {
        return 'icon-head head-cms-page';
    }
}