<?php
abstract class Topart_Productimport_Helper_Abstract extends Mage_Core_Helper_Abstract
{

	protected $_sourceModel		= null;

	// ===== Option Functions ======================================================================

	public function getSourceModel($path=null)
	{
		if ($this->_sourceModel == null)
			Mage::throwException($this->__('Source Model not defined for helper: %s',get_class($this)));
		$source = Mage::getSingleton($this->_sourceModel);
		if (!is_object($source))
			Mage::throwException($this->__('Source Model: %s not found for helper: %s',$this->_sourceModel,get_class($this)));
		return $source;
	}

	public function getOptions($path,$asHash=true,$isSelector=false)
	{
		$source = $this->getSourceModel($path);
		$source->setPath($path);
		if ($asHash)
			return $source->toOptionHash($isSelector);
		else
			return $source->toOptionArray($isSelector);
	}

	public function getOptionLabel($path,$value)
	{
		$source = $this->getSourceModel($path);
		$source->setPath($path);
		return $source->getOptionLabel($value);
	}

	public function getOptionFromLabel($path,$label)
	{
		$source = $this->getSourceModel($path);
		$source->setPath($path);
		return $source->getOptionFromLabel($label);
	}

}

