<?php
class Topart_ProductImport_Adminhtml_ProductImport_ProductImportController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
    {
		$hlp = Mage::helper('productimport');
		$this->_title($this->__('Topart Product Import'));
		$this->loadLayout();
		$this->_setActiveMenu('productImport/manager');
		//$this->_addBreadcrumb($hlp->__('Items'), $hlp->__('Items'));
        $this->_addContent(
            $this->getLayout()->createBlock('Mage_Adminhtml_Block_Abstract')
            ->setTemplate('topart/productimport/manager.phtml')
        );
		$this->renderLayout();
    }

    public function runAction()
    {
        /*** PROCESS ***/
        $helper = Mage::helper('productimport/data');
        if (isset($_POST['topart_productimport_process']))
        {
            $helper->process();
        }

        return $this->indexAction();
    }

	protected function _isAllowed()
    {
        return true;
		//return Mage::getSingleton('admin/session')->isAllowed('productimport');
	}

}

