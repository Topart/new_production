<?php
/**
 * Alert type model
 *
 * @category    Bmbleb
 * @package     Bmbleb_Alert
 * @author      Jeremy Dost <jdost@sharpdotinc.com>
 */
class Socketware_Bmbleb_Model_Alert_Priority extends Mage_Core_Model_Abstract
{
	const LOW = 2;
    const MED = 3;
    const HI = 4;
	
	public function getOptions()
	{
    	$options = new Varien_Object(array(
            self::LOW => Mage::helper('bmbleb')->__('Low'),
            self::MED => Mage::helper('bmbleb')->__('Medium'),
            self::HI => Mage::helper('bmbleb')->__('High'),
        ));
    	return $options->getData();
	}
	
	
}
	