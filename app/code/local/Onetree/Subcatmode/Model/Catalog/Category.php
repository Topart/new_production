<?php
/**
 * Onetree SubCategory Display Mode
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to support@onetree.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade SubCategory Display Mode to newer
 * versions in the future.
 *
 * @category    Onetree
 * @package     Onetree_Subcatmode
 * @copyright   Copyright (c) 2012 Onetree. (http://www.onetree.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Onetree subcatmode
 *
 * @category   Onetree
 * @package    Onetree_Subcatmode
 * @author     Onetree Team <support@onetree.com>
 */
class Onetree_Subcatmode_Model_Catalog_Category extends Mage_Catalog_Model_Category
{
    /**
     * New Display Mode.
     * Can display only categories children
     */
    const DM_SUBCATEGORIES            = 'SUBCATEGORIES';


    /**
     * Retrieve image URL
     *
     * @return string
     */
    public function getCollectionImageUrl()
    {
        $url = false;
        if ($image = $this->getCollectionImage()) {
            $url = Mage::getBaseUrl('media').'catalog/category/'.$image;
        }
        return $url;
    }
}
