<?php

/**
 * Created by PhpStorm.
 * User: ikis1520
 * Date: 30/06/15
 * Time: 20:35
 */
class Onetree_Seo_Adminhtml_CustomController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
/*        $this->loadLayout()
            ->_setActiveMenu('mycustomtab')
            ->_title($this->__('Index Action'));

        $this->_addLeft($this->getLayout()
            ->createBlock('core/text')
            ->setText('<h1>Left Block</h1>'));

        $block = $this->getLayout()
            ->createBlock('core/text')
            ->setText('<h1>Main Block</h1>');
        $this->_addContent($block);


        //create a text block with the name of "example-block"
        $block = $this->getLayout()
            ->createBlock('core/text', 'example-block')
            ->setText('<h1>This is a text block</h1>');

        $this->_addContent($block);*/
        $this->loadLayout();
        $this->_setActiveMenu('onetree_seo/item');

        $this->renderLayout();
    }

    public function listAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('mycustomtab')
            ->_title($this->__('List Action'));

        // my stuff

        $this->renderLayout();
    }

    /**
     * Edit action - shows the edit form
     */
    public function editAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('onetree_seo/item');
        $this->renderLayout();
    }


}