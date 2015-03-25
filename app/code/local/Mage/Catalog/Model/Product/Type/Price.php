<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Product type price model
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Model_Product_Type_Price
{
    const CACHE_TAG = 'PRODUCT_PRICE';

    static $attributeCache = array();

    /**
     * Default action to get price of product
     *
     * @return decimal
     */
    public function getPrice($product)
    {
        return $product->getData('price');
    }

    /**
     * Get base price with apply Group, Tier, Special prises
     *
     * @param Mage_Catalog_Model_Product $product
     * @param float|null $qty
     *
     * @return float
     */
    public function getBasePrice($product, $qty = null)
    {
        $price = (float)$product->getPrice();
        return min($this->_applyGroupPrice($product, $price), $this->_applyTierPrice($product, $qty, $price),
            $this->_applySpecialPrice($product, $price)
        );
    }


    /**
     * Retrieve product final price
     *
     * @param float|null $qty
     * @param Mage_Catalog_Model_Product $product
     * @return float
     */
    public function getFinalPrice($qty = null, $product)
    {
        if (is_null($qty) && !is_null($product->getCalculatedFinalPrice())) {
            return $product->getCalculatedFinalPrice();
        }

        $finalPrice = $this->getBasePrice($product, $qty);
        $product->setFinalPrice($finalPrice);

        Mage::dispatchEvent('catalog_product_get_final_price', array('product' => $product, 'qty' => $qty));

        $finalPrice = $product->getData('final_price');
        $finalPrice = $this->_applyOptionsPrice($product, $qty, $finalPrice);
        $finalPrice = max(0, $finalPrice);
        $product->setFinalPrice($finalPrice);

        return $finalPrice;
    }

    public function getChildFinalPrice($product, $productQty, $childProduct, $childProductQty)
    {
        return $this->getFinalPrice($childProductQty, $childProduct);
    }

    /**
     * Apply group price for product
     *
     * @param Mage_Catalog_Model_Product $product
     * @param float $finalPrice
     * @return float
     */
    protected function _applyGroupPrice($product, $finalPrice)
    {
        $groupPrice = $product->getGroupPrice();
        if (is_numeric($groupPrice)) {
            $finalPrice = min($finalPrice, $groupPrice);
        }
        return $finalPrice;
    }

    /**
     * Get product group price
     *
     * @param Mage_Catalog_Model_Product $product
     * @return float
     */
    public function getGroupPrice($product)
    {

        $groupPrices = $product->getData('group_price');

        if (is_null($groupPrices)) {
            $attribute = $product->getResource()->getAttribute('group_price');
            if ($attribute) {
                $attribute->getBackend()->afterLoad($product);
                $groupPrices = $product->getData('group_price');
            }
        }

        if (is_null($groupPrices) || !is_array($groupPrices)) {
            return $product->getPrice();
        }

        $customerGroup = $this->_getCustomerGroupId($product);

        $matchedPrice = $product->getPrice();
        foreach ($groupPrices as $groupPrice) {
            if ($groupPrice['cust_group'] == $customerGroup && $groupPrice['website_price'] < $matchedPrice) {
                $matchedPrice = $groupPrice['website_price'];
                break;
            }
        }

        return $matchedPrice;
    }

    /**
     * Apply tier price for product if not return price that was before
     *
     * @param   Mage_Catalog_Model_Product $product
     * @param   float $qty
     * @param   float $finalPrice
     * @return  float
     */
    protected function _applyTierPrice($product, $qty, $finalPrice)
    {
        if (is_null($qty)) {
            return $finalPrice;
        }

        $tierPrice  = $product->getTierPrice($qty);
        if (is_numeric($tierPrice)) {
            $finalPrice = min($finalPrice, $tierPrice);
        }
        return $finalPrice;
    }

    /**
     * Get product tier price by qty
     *
     * @param   float $qty
     * @param   Mage_Catalog_Model_Product $product
     * @return  float
     */
    public function getTierPrice($qty = null, $product)
    {
        $allGroups = Mage_Customer_Model_Group::CUST_GROUP_ALL;
        $prices = $product->getData('tier_price');

        if (is_null($prices)) {
            $attribute = $product->getResource()->getAttribute('tier_price');
            if ($attribute) {
                $attribute->getBackend()->afterLoad($product);
                $prices = $product->getData('tier_price');
            }
        }

        if (is_null($prices) || !is_array($prices)) {
            if (!is_null($qty)) {
                return $product->getPrice();
            }
            return array(array(
                'price'         => $product->getPrice(),
                'website_price' => $product->getPrice(),
                'price_qty'     => 1,
                'cust_group'    => $allGroups,
            ));
        }

        $custGroup = $this->_getCustomerGroupId($product);
        if ($qty) {
            $prevQty = 1;
            $prevPrice = $product->getPrice();
            $prevGroup = $allGroups;

            foreach ($prices as $price) {
                if ($price['cust_group']!=$custGroup && $price['cust_group']!=$allGroups) {
                    // tier not for current customer group nor is for all groups
                    continue;
                }
                if ($qty < $price['price_qty']) {
                    // tier is higher than product qty
                    continue;
                }
                if ($price['price_qty'] < $prevQty) {
                    // higher tier qty already found
                    continue;
                }
                if ($price['price_qty'] == $prevQty && $prevGroup != $allGroups && $price['cust_group'] == $allGroups) {
                    // found tier qty is same as current tier qty but current tier group is ALL_GROUPS
                    continue;
                }
                if ($price['website_price'] < $prevPrice) {
                    $prevPrice  = $price['website_price'];
                    $prevQty    = $price['price_qty'];
                    $prevGroup  = $price['cust_group'];
                }
            }
            return $prevPrice;
        } else {
            $qtyCache = array();
            foreach ($prices as $i => $price) {
                if ($price['cust_group'] != $custGroup && $price['cust_group'] != $allGroups) {
                    unset($prices[$i]);
                } else if (isset($qtyCache[$price['price_qty']])) {
                    $j = $qtyCache[$price['price_qty']];
                    if ($prices[$j]['website_price'] > $price['website_price']) {
                        unset($prices[$j]);
                        $qtyCache[$price['price_qty']] = $i;
                    } else {
                        unset($prices[$i]);
                    }
                } else {
                    $qtyCache[$price['price_qty']] = $i;
                }
            }
        }

        return ($prices) ? $prices : array();
    }

    protected function _getCustomerGroupId($product)
    {
        if ($product->getCustomerGroupId()) {
            return $product->getCustomerGroupId();
        }
        return Mage::getSingleton('customer/session')->getCustomerGroupId();
    }

    /**
     * Apply special price for product if not return price that was before
     *
     * @param   Mage_Catalog_Model_Product $product
     * @param   float $finalPrice
     * @return  float
     */
    protected function _applySpecialPrice($product, $finalPrice)
    {
        return $this->calculateSpecialPrice($finalPrice, $product->getSpecialPrice(), $product->getSpecialFromDate(),
                        $product->getSpecialToDate(), $product->getStore()
        );
    }

    /**
     * Count how many tier prices we have for the product
     *
     * @param   Mage_Catalog_Model_Product $product
     * @return  int
     */
    public function getTierPriceCount($product)
    {
        $price = $product->getTierPrice();
        return count($price);
    }

    /**
     * Get formatted by currency tier price
     *
     * @param   float $qty
     * @param   Mage_Catalog_Model_Product $product
     * @return  array || float
     */
    public function getFormatedTierPrice($qty=null, $product)
    {
        $price = $product->getTierPrice($qty);
        if (is_array($price)) {
            foreach ($price as $index => $value) {
                $price[$index]['formated_price'] = Mage::app()->getStore()->convertPrice(
                        $price[$index]['website_price'], true
                );
            }
        }
        else {
            $price = Mage::app()->getStore()->formatPrice($price);
        }

        return $price;
    }

    /**
     * Get formatted by currency product price
     *
     * @param   Mage_Catalog_Model_Product $product
     * @return  array || float
     */
    public function getFormatedPrice($product)
    {
        return Mage::app()->getStore()->formatPrice($product->getFinalPrice());
    }

    /**
     * Apply options price
     *
     * @param Mage_Catalog_Model_Product $product
     * @param int $qty
     * @param float $finalPrice
     * @return float
     */
    protected function _applyOptionsPrice($product, $qty, $finalPrice)
    {
        $option_sku = "";
        $size_ui_position = 0;
        $size_ui = 0;
        $mats_price = 0.0;
        $mats_size = 0;
        $canvas_stretching_ui_price = 0;
        $frame_ui_price = 0;

        if ($optionIds = $product->getCustomOption('option_ids')) {
            $basePrice = $finalPrice;
            foreach (explode(',', $optionIds->getValue()) as $optionId) {
                if ($option = $product->getOptionById($optionId)) {
                    $confItemOption = $product->getCustomOption('option_'.$option->getId());

                    $group = $option->groupFactory($option->getType())
                        ->setOption($option)
                        ->setConfigurationItemOption($confItemOption);

                    // Dynamic price update based on the selection of specific custom options: frame and mats
                    switch($option->getTitle())
                    {
                        // Get the size UI and add the size price to the total price
                        case 'Size':
                            $option_sku = $group->getOptionSku($confItemOption->getValue(), $basePrice);

                            $size_ui_position = strpos($option_sku, 'ui_') + 3;
                            $size_ui = substr($option_sku, $size_ui_position);

                            $finalPrice += $group->getOptionPrice($confItemOption->getValue(), $basePrice);
                        break;

                        

                        // Multiply the mats UI price by the size UI
                        case 'Mat':

                            $option_sku = $group->getOptionSku($confItemOption->getValue(), $basePrice);

                            $mats_size_position = strpos($option_sku, '_#') - 1;
                            $mats_size = substr($option_sku, $mats_size_position) * 4;
                            
                            $mats_ui_price = $group->getOptionPrice($confItemOption->getValue(), $basePrice);
                        
                        break;

                        // Multiply the frame UI price by the size UI
                        case 'Frame':
                            $frame_ui_price = $group->getOptionPrice($confItemOption->getValue(), $basePrice);
                        break;

                        // Multiply the canvas stretching price UI price by the size UI
                        case 'Canvas Stretching':
                            $canvas_stretching_ui_price = $group->getOptionPrice($confItemOption->getValue(), $basePrice);
                        break;

                        // In any other case, just add the option price
                        default:
                            $finalPrice += $group->getOptionPrice($confItemOption->getValue(), $basePrice);
                    }
                    
                }
            }
        }

        if ( $mats_size == 0 )
            $product_price = $finalPrice + number_format((float)( $frame_ui_price + $canvas_stretching_ui_price ), 2, '.', '') * $size_ui;
        else
            $product_price = $finalPrice + number_format((float)( $frame_ui_price + $canvas_stretching_ui_price ), 2, '.', '') * ( $size_ui + $mats_size ) + $mats_ui_price * ($size_ui + $mats_size);

        // Add the flat mounting price if a frame was added
        if ( $frame_ui_price != 0.00 )
            $product_price += 12.00;

        // Sum the total price to real framing and mats prices
        return $product_price;
    }

    /**
     * Calculate product price based on special price data and price rules
     *
     * @param   float $basePrice
     * @param   float $specialPrice
     * @param   string $specialPriceFrom
     * @param   string $specialPriceTo
     * @param   float|null|false $rulePrice
     * @param   mixed $wId
     * @param   mixed $gId
     * @param   null|int $productId
     * @return  float
     */
    public static function calculatePrice($basePrice, $specialPrice, $specialPriceFrom, $specialPriceTo,
            $rulePrice = false, $wId = null, $gId = null, $productId = null)
    {
        Varien_Profiler::start('__PRODUCT_CALCULATE_PRICE__');
        if ($wId instanceof Mage_Core_Model_Store) {
            $sId = $wId->getId();
            $wId = $wId->getWebsiteId();
        } else {
            $sId = Mage::app()->getWebsite($wId)->getDefaultGroup()->getDefaultStoreId();
        }

        $finalPrice = $basePrice;
        if ($gId instanceof Mage_Customer_Model_Group) {
            $gId = $gId->getId();
        }

        $finalPrice = self::calculateSpecialPrice($finalPrice, $specialPrice, $specialPriceFrom, $specialPriceTo, $sId);

        if ($rulePrice === false) {
            $storeTimestamp = Mage::app()->getLocale()->storeTimeStamp($sId);
            $rulePrice = Mage::getResourceModel('catalogrule/rule')
                ->getRulePrice($storeTimestamp, $wId, $gId, $productId);
        }

        if ($rulePrice !== null && $rulePrice !== false) {
            $finalPrice = min($finalPrice, $rulePrice);
        }

        $finalPrice = max($finalPrice, 0);
        Varien_Profiler::stop('__PRODUCT_CALCULATE_PRICE__');
        return $finalPrice;
    }

    /**
     * Calculate and apply special price
     *
     * @param float $finalPrice
     * @param float $specialPrice
     * @param string $specialPriceFrom
     * @param string $specialPriceTo
     * @param mixed $store
     * @return float
     */
    public static function calculateSpecialPrice($finalPrice, $specialPrice, $specialPriceFrom, $specialPriceTo,
            $store = null)
    {
        if (!is_null($specialPrice) && $specialPrice != false) {
            if (Mage::app()->getLocale()->isStoreDateInInterval($store, $specialPriceFrom, $specialPriceTo)) {
                $finalPrice     = min($finalPrice, $specialPrice);
            }
        }
        return $finalPrice;
    }

    /**
     * Check is tier price value fixed or percent of original price
     *
     * @return bool
     */
    public function isTierPriceFixed()
    {
        return $this->isGroupPriceFixed();
    }

    /**
     * Check is group price value fixed or percent of original price
     *
     * @return bool
     */
    public function isGroupPriceFixed()
    {
        return true;
    }
}
