<?php
/**
 * Alert type model
 *
 * @category    Bmbleb
 * @package     Bmbleb_Alert
 * @author      Jeremy Dost <jdost@sharpdotinc.com>
 */
class Socketware_Bmbleb_Model_Alert_Status extends Mage_Core_Model_Abstract
{
	const UNOPENED = 0;
    const SKIPPED = 1;
    const COMPLETED = 2;

    public function getOptions()
    {
    	$options = new Varien_Object(array(
            self::UNOPENED => Mage::helper('bmbleb')->__('New'),
            self::SKIPPED => Mage::helper('bmbleb')->__('Skipped'),
            self::COMPLETED => Mage::helper('bmbleb')->__('Completed'),
        ));
    	return $options->getData();
    }
    
	
}
	