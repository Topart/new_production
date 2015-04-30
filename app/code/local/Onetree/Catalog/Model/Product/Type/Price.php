<?php

class Onetree_Catalog_Model_Product_Type_Price extends Mage_Catalog_Model_Product_Type_Price {

    /**
     * Apply options price
     *
     * @param Mage_Catalog_Model_Product $product
     * @param int $qty
     * @param float $finalPrice
     * @return float
     */
    protected function _applyOptionsPrice($product, $qty, $finalPrice) {
        $option_sku = "";
        $size_ui_position = 0;
        $size_ui = 0;
        $mats_price = 0.0;
        $mats_size = 0;
        $canvas_stretching_ui_price = 0;
        $frame_ui_price = 0;
        $sizeSku = '';

        if ($optionIds = $product->getCustomOption('option_ids')) {
            $basePrice = $finalPrice;
            foreach (explode(',', $optionIds->getValue()) as $optionId) {
                if ($option = $product->getOptionById($optionId)) {
                    $confItemOption = $product->getCustomOption('option_' . $option->getId());

                    $group = $option->groupFactory($option->getType())->setOption($option)->setConfigurationItemOption($confItemOption);

                    // Dynamic price update based on the selection of specific custom options: frame and mats
                    switch ($option->getTitle()) {
                        // Get the size UI and add the size price to the total price
                        case 'Size':
                            $sizeSku = $option_sku = $group->getOptionSku($confItemOption->getValue(), $basePrice);

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

        if ($mats_size == 0) {
            $product_price = $finalPrice + number_format((float)($frame_ui_price + $canvas_stretching_ui_price), 2, '.', '') * $size_ui;
        } else {
            $product_price = $finalPrice + number_format((float)($frame_ui_price + $canvas_stretching_ui_price), 2, '.', '') * ($size_ui + $mats_size) + $mats_ui_price * ($size_ui + $mats_size);
        }

        // Add the flat mounting price if a frame was added
        if ($frame_ui_price != 0.00)
            $product_price += 12.00;

        // adding stretching charge for canvas
        // starts with 'size_canvas_' and contains '_treatment_'
        if (strrpos($sizeSku, 'size_canvas_', -strlen($sizeSku)) !== false && strpos($sizeSku, '_treatment_') !== false) {
            // stretching calculation is always based on '_treatment_1_' (WHITE BORDER)
            if (strpos($sizeSku, '_treatment_1_') === false) {
                $patternStart = substr($sizeSku, 0, strpos($sizeSku, '_treatment_')) . '_treatment_1_';

                foreach ($product->getOptions() as $option) {
                    $values = $option->getValues();

                    if ($option->getTitle() == 'Size') {
                        foreach ($values as $value) {
                            // starts with pattern
                            if (strrpos($value->getSku(), $patternStart, -strlen($value->getSku())) !== false) {
                                $sizeSku = $value->getSku();
                            }
                        }
                    }
                }
            }

            // stretching price
            foreach ($product->getOptions() as $option) {
                $values = $option->getValues();

                if ($option->getTitle() == 'Canvas Stretching') {
                    foreach ($values as $value) {
                        $canvas_stretching_ui_price = $value->getPrice();
                    }
                }
            }

            // extract image United Inches
            if (preg_match('/_ui_\s*(.*?)_/', $sizeSku, $iUI)) {
                $product_price += $canvas_stretching_ui_price * $iUI[1];
            }
        }

        // Sum the total price to real framing and mats prices
        return $product_price;
    }

}
