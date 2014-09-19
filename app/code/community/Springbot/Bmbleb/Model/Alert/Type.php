<?php
/**
 * Alert type model
 *
 * @category    Bmbleb
 * @package     Bmbleb_Alert
 * @author      Jeremy Dost <jdost@sharpdotinc.com>
 */
class Socketware_Bmbleb_Model_Alert_Type extends Mage_Core_Model_Abstract
{
	//These are the current possible alerttypeids
    const LENGTHOFRESIDENCERESET = 200;
    const OCCUPATION = 300;
    const HOMEOWNERSTATUSRENTTOOWN = 400;
    const HOMEOWNERSTATUSOWNTORENT = 410;
    const MARITALSTATUSSINGLETOMARRIED = 500;
    const MARITALSTATUSMARRIEDTOSINGLE = 510;
    const CHILDRENNOTOYES = 600;
    const EDUCATIONCOMPLETEDCOLLEGE = 700;
    const EDUCATIONCOMPLETEDHIGHSCHOOL = 710;
    const VEHICLENEW = 800;
    const HIGHNETWORTHNOTOYES = 900;
    const LOANTOVALUERATIOOVER80 = 1000;
    const LOANTOVALUERATIOUNDER80 = 1010;
    const TWITTERNEW = 1100;
    const FACEBOOKNEW = 1200;
    const LINKEDINNEW = 1300;
    const IMAGEURLNEW = 1400;
    const COMPANYCHANGE = 1500;
    const TITLECHANGE = 1600;
    const LOCATIONCHANGE = 1700;	
	
	public function getOptions()
	{
    	$options = new Varien_Object(array(
            self::TWITTERNEW => Mage::helper('bmbleb')->__('New Twitter'),
            self::FACEBOOKNEW => Mage::helper('bmbleb')->__('New Facebook'),
            self::LINKEDINNEW => Mage::helper('bmbleb')->__('New LinkedIn'),
        ));
    	return $options->getData();
	}
	
	
}
	