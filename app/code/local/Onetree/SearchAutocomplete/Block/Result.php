<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Sphinx Search Ultimate
 * @version   2.3.2
 * @build     1129
 * @copyright Copyright (C) 2015 Mirasvit (http://mirasvit.com/)
 */


/**
 * Блок вывода результатов поиска. Основная задача - дочерние блоки всех включенных индексов, ограничить кол-во выводимых елементов
 *
 * @category Mirasvit
 * @package  Mirasvit_SearchAutocomplete
 */
class Onetree_SearchAutocomplete_Block_Result extends Mirasvit_SearchAutocomplete_Block_Result
{

    public function getPrice($product){
        $options = $product->getProductOptionsCollection();
        $cheapestPrice = 0;
        foreach($options as $option) {
            if (!is_null($option) && $option->getTitle() == "Size") {

                $optionValues = $option->getValues();
                $aPrices = array();

                foreach ($optionValues as $value) {
                    if ($value->getPrice() > 0) {
                        $aPrices[] = $value->getPrice();
                    }
                }

                if (count($aPrices)) {
                    sort($aPrices);
                    $cheapestPrice = $this->helper('core')->formatCurrency($aPrices[0]);
                }
            }
        }
       return "From ". $cheapestPrice;
    }


}