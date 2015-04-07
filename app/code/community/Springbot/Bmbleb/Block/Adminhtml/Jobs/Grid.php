<?php

class Springbot_Bmbleb_Block_Adminhtml_Jobs_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	public function __construct()
	{
		parent::__construct();
		$this->setId('id');
		$this->setDefaultDir('desc');
		$this->setUseAjax(true);
		$this->setSaveParametersInSession(true);
	}

	protected function _prepareCollection()
	{
		$collection = Mage::getResourceModel('combine/cron_queue_collection');
		$this->setCollection($collection);

		return parent::_prepareCollection();
	}

	protected function _prepareColumns()
	{
		$this->addColumn('id', array(
			'header'    => $this->__('Id'),
			'width'     => '80',
			'align'     => 'center',
			'index'     => 'id'
		));

		$this->addColumn('method', array(
			'header'    => $this->__('Method'),
			'index'     => 'method'
		));

		$this->addColumn('args', array(
			'header'    => $this->__('Arguments'),
			'index'     => 'args'
		));

		$this->addColumn('priority', array(
			'header'    => $this->__('Priority'),
			'width'     => '80',
			'index'     => 'priority'
		));

		$this->addColumn('attempts', array(
			'header'    => $this->__('Attempts'),
			'width'     => '80',
			'index'     => 'attempts'
		));

		$this->addColumn('error', array(
			'header'    => $this->__('Last Error'),
			'width'     => '80',
			'index'     => 'error'
		));

		$this->addColumn('locked_at', array(
			'header'    => $this->__('locked_at'),
			'index'     => 'locked_at',
			'type'      => 'datetime'
		));

		$this->addColumn('created_at', array(
			'header'    => $this->__('created_at'),
			'index'     => 'created_at',
			'type'      => 'datetime'
		));

		return parent::_prepareColumns();
	}

	protected function _prepareMassaction()
	{
		$this->setMassactionIdField('id');
		$this->getMassactionBlock()->setFormFieldName('job_ids');
		$this->getMassactionBlock()->setUseSelectAll(false);

		$this->getMassactionBlock()->addItem('run_jobs', array(
			'label'=> $this->__('Run selected jobs'),
			'url'  => $this->getUrl('*/*/run'),
		));

		$this->getMassactionBlock()->addItem('deleted_jobs', array(
			'label'=> $this->__('Deleted selected jobs'),
			'url'  => $this->getUrl('*/*/delete'),
		));

		return $this;
	}

	public function getGridUrl()
	{
		return $this->getUrl('*/*/grid', array('_current'=>true));
	}
}
