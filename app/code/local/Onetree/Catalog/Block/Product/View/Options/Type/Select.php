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
 * @copyright   Copyright (c) 2013 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Product options text type block
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Onetree_Catalog_Block_Product_View_Options_Type_Select extends Mage_Catalog_Block_Product_View_Options_Type_Select {

    /**
     * Return html for control element
     *
     * @return string
     */
    public function getValuesHtml() {
        $_option = $this->getOption();
        $configValue = $this->getProduct()->getPreconfiguredValues()->getData('options/' . $_option->getId());
        $store = $this->getProduct()->getStore();

        if ($_option->getType() == Mage_Catalog_Model_Product_Option::OPTION_TYPE_DROP_DOWN || $_option->getType() == Mage_Catalog_Model_Product_Option::OPTION_TYPE_MULTIPLE) {
            $require = ($_option->getIsRequire()) ? ' required-entry' : '';
            $extraParams = '';
            $select = $this->getLayout()->createBlock('core/html_select')->setData(array('id' => 'select_' . $_option->getId(), 'class' => $require . ' product-custom-option'));
            if ($_option->getType() == Mage_Catalog_Model_Product_Option::OPTION_TYPE_DROP_DOWN) {
                $select->setName('options[' . $_option->getid() . ']')->addOption('', $this->__('-- Please Select --'));
            } else {
                $select->setName('options[' . $_option->getid() . '][]');
                $select->setClass('multiselect' . $require . ' product-custom-option');
            }
            foreach ($_option->getValues() as $_value) {
                $priceStr = $this->_formatPrice(array('is_percent' => ($_value->getPriceType() == 'percent'), 'pricing_value' => $_value->getPrice(($_value->getPriceType() == 'percent'))), false);
                $select->addOption($_value->getOptionTypeId(), $_value->getTitle() . ' ' . $priceStr . '', array('price' => $this->helper('core')->currencyByStore($_value->getPrice(true), $store, false)));
            }
            if ($_option->getType() == Mage_Catalog_Model_Product_Option::OPTION_TYPE_MULTIPLE) {
                $extraParams = ' multiple="multiple"';
            }
            if (!$this->getSkipJsReloadPrice()) {
                $extraParams .= ' onchange="opConfig.reloadPrice()"';
            }
            $select->setExtraParams($extraParams);

            if ($configValue) {
                $select->setValue($configValue);
            }

            return $select->getHtml();
        }

        if ($_option->getType() == Mage_Catalog_Model_Product_Option::OPTION_TYPE_RADIO || $_option->getType() == Mage_Catalog_Model_Product_Option::OPTION_TYPE_CHECKBOX) {
            $selectHtml = '<ul id="options-' . $_option->getId() . '-list" class="options-list">';
            $require = ($_option->getIsRequire()) ? ' validate-one-required-by-name' : '';
            $arraySign = '';
            switch ($_option->getType()) {
                case Mage_Catalog_Model_Product_Option::OPTION_TYPE_RADIO:
                    $type = 'radio';
                    $class = 'radio';
                    if (!$_option->getIsRequire()) {
                        $selectHtml .= '<li><input type="radio" id="options_' . $_option->getId() . '" class="' . $class . ' product-custom-option" name="options[' . $_option->getId() . ']"' . ($this->getSkipJsReloadPrice() ? '' : ' onclick="opConfig.reloadPrice()"') . ' value="" checked="checked" /><span class="label"><label for="options_' . $_option->getId() . '">' . $this->__('None') . '</label></span></li>';
                    }
                    break;
                case Mage_Catalog_Model_Product_Option::OPTION_TYPE_CHECKBOX:
                    $type = 'checkbox';
                    $class = 'checkbox';
                    $arraySign = '[]';
                    break;
            }
            $count = 1;
            foreach ($_option->getValues() as $_value) {
                $count++;

                $priceStr = $this->_formatPrice(array('is_percent' => ($_value->getPriceType() == 'percent'), 'pricing_value' => $_value->getPrice($_value->getPriceType() == 'percent')));

                $htmlValue = $_value->getOptionTypeId();
                if ($arraySign) {
                    $checked = (is_array($configValue) && in_array($htmlValue, $configValue)) ? 'checked' : '';
                } else {
                    $checked = $configValue == $htmlValue ? 'checked' : '';
                }

                $selectHtml .= "<li class='" . strtolower(str_replace(" ", "_", $_value->getSku())) . "'>" . $this->optionImage($_option, $_value) . '<input data-option-sku="' . strtolower(str_replace(" ", "_", $_value->getSku())) . '" type="' . $type . '" class="' . $class . ' ' . $require . ' product-custom-option"' . ($this->getSkipJsReloadPrice() ? '' : ' onclick="opConfig.reloadPrice()"') . ' name="options[' . $_option->getId() . ']' . $arraySign . '" id="options_' . $_option->getId() . '_' . $count . '" value="' . $htmlValue . '" ' . $checked . ' price="' . $this->helper('core')->currencyByStore($_value->getPrice(true), $store, false) . '" />' . '<span class="label"><label for="options_' . $_option->getId() . '_' . $count . '">' . $_value->getTitle() . ' ' . $priceStr . '</label></span>';
                if ($_option->getIsRequire()) {
                    $selectHtml .= '<script type="text/javascript">' . '$(\'options_' . $_option->getId() . '_' . $count . '\').advaiceContainer = \'options-' . $_option->getId() . '-container\';' . '$(\'options_' . $_option->getId() . '_' . $count . '\').callbackFunction = \'validateOptionsCallback\';' . '</script>';
                }
                $selectHtml .= '</li>';
            }
            $selectHtml .= '</ul>';

            return $selectHtml;
        }
    }

    public function optionImage($option, $value) {
        $html = '';
        $title = strtolower(str_replace(" ", "_", $option->getTitle()));
        $thumbnailPath = "http://d3odr912zwpuhm.cloudfront.net/thumbnail_images/";
        $productSku = $this->getProduct()->getSku();

        if ($this->endsWith($productSku, "DG")) {
            $dgSuffixIndex = strrpos($productSku, "DG");
            $imageName = substr_replace($productSku, "", $dgSuffixIndex, 2) . ".jpg";
        } else {
            $imageName = $productSku . ".jpg";
        }

        $thumbnailUrl = $thumbnailPath . $imageName;
        $sku = $value->getData('sku');
        
        if (!in_array($title, array("size", "frame", "mat"))) {
            $folded = '';

            if (in_array($sku, array("border_treatment_3_inches_of_white", "border_treatment_2_inches_of_black_and_1_inch_of_white"))) {
                $folded = '<div class="border_folded_top"></div>
                           <div class="border_folded_bottom"></div>';
            }
            
            $html = '<div id="custom_option_' . $sku . '_background" class="background_border">' . $folded . '
                        <div id="custom_option_' . $sku . '" class="custom_option_viewport">
                            <img src="' . $thumbnailUrl . '" />
                        </div>';

            if ($sku == "border_treatment_2_inches_mirrored_and_1_inch_of_white") {
                for ($count = 7; $count >= 1; $count--) {
                    $html .= '<div id="custom_option_' . $sku . '" class="custom_option_viewport" 
                               style="position: absolute; right: -' . $count . 'px; top: -' . $count . 'px;">
                             <img src="' . $thumbnailUrl . '" />
                          </div>';
                }
            }

            $html .= '</div>';
        } elseif ($title == "mat") {
            if ($this->endsWith($sku, '2')) {
                $html = '<div class="mats_2_inches mats_style"><div class="mats_l_shape"></div></div>';
            } elseif ($this->endsWith($sku, '3')) {
                $html = '<div class="mats_3_inches mats_style"><div class="mats_l_shape"></div></div>';
            } else {
                $html = '<div id="mats_none" class="mats_style"></div>';
            }
        }

        return $html;
    }

    public function endsWith($haystack, $needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    public function getSize($option_sku) {
        $width = "";
        $length = "";
        
        if($option_sku == "size_photopaper_petite_ui_24_width_12_length_12"){
            $a = 0;
        }
        
        $size_value_array = explode("_", $option_sku);

        $count = 0;

        foreach ($size_value_array as $value) {
            if (strtolower(trim($value)) == "width") {
                if (isset($size_value_array[$count + 1])) {
                    $width = $size_value_array[$count + 1];
                }
            } elseif (strtolower(trim($value)) == "length") {
                if (isset($size_value_array[$count + 1])) {
                    $length = $size_value_array[$count + 1];
                }
            }

            $count++;
        }

        if ($width !== "" && $length !== "") {
            return array("width" => $width, "length" => $length);
        }

        return false;
    }

}
