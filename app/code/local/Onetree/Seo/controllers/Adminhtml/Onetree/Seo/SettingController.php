<?php
/**
 * Examples
 *
 * PHP Version 5
 *
 * @category  Examples
 * @package   Examples_AdminGridAndForm
 * @author    Mike Whitby <me@mikewhitby.co.uk>
 * @copyright Copyright (c) 2012 Mike Whitby (http://www.mikewhitby.co.uk)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      N/A
 */

/**
 * Thing controller
 *
 * @category Examples
 * @package  Examples_AdminGridAndForm
 * @author   Mike Whitby <me@mikewhitby.co.uk>
 */
class Onetree_Seo_Adminhtml_Onetree_Seo_SettingController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Index action - shows the grid
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('onetree_seo/item');
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

    public function saveAction(){
        if ($data = $this->getRequest()->getPost()) {
            //init model and set data
            $model = Mage::getModel('seo/information');
            if ($id = $this->getRequest()->getParam('id')) {//the parameter name may be different
                $model->load($id);
            }
            $model->addData($data);
            try{
                //try to save it
                $model->save();
                Mage::getSingleton('adminhtml/session')->addSuccess('Saved');
                //redirect to grid.
                $this->_redirect('*/*/');
            }
            catch (Exception $e){
                //if there is an error return to edit
                Mage::getSingleton('adminhtml/session')->addError('Not Saved. Error:'.$e->getMessage());
                Mage::getSingleton('adminhtml/session')->setExampleFormData($data);
                $this->_redirect('*/*/edit', array('id'=>$model->getId(), '_current'=>true));
            }
        }
    }
}
