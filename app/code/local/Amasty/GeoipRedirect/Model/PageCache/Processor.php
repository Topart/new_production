<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2015 Amasty (https://www.amasty.com)
 * @package Amasty_GeoipRedirect
 */

class Amasty_GeoipRedirect_Model_PageCache_Processor extends Enterprise_PageCache_Model_Processor {

    public function extractContent($content) {
        $xmlFile = Mage::getBaseDir('etc') . DS . 'modules' . DS . 'Amasty_GeoipRedirect.xml';
        if (file_exists($xmlFile) && is_readable($xmlFile)) {
            $xml = simplexml_load_file($xmlFile);
            $data = Mage::helper('core')->xmlToAssoc($xml);
            if (isset($data['modules']['Amasty_GeoipRedirect']['active']) && $data['modules']['Amasty_GeoipRedirect']['active'] == 'true') {
                $resource = Mage::getSingleton('core/resource');
                $connection = $resource->getConnection('core_write');
                $coreTable = $resource->getTableName('core_config_data');
                $isEnabled = $connection->fetchOne("select value from $coreTable where path='amgeoipredirect/general/enable'");

                $cookie = Mage::getSingleton('core/cookie');
                $allowRedirect = $cookie->get('am_geoipredirect');

                if ($isEnabled !== '0' && !$allowRedirect) {
                    return false;
                }
            }
        }


       return parent::extractContent($content);
    }
}