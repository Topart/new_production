<?php

class Springbot_Imageoverride_Combine_Helper_Parser extends Springbot_Combine_Helper_Parser {

	public function getImageUrl($product) {
		if($product instanceof Mage_Catalog_Model_Product) {

            $topProduct = $this->getTopLevelSku($product);
            $image_path = 'http://d3odr912zwpuhm.cloudfront.net/small_images/';
			$skuImg = $this->cleanSkuImg(strtoupper($topProduct),'DG',$image_path);
			$image_path .= $skuImg;
			return $image_path;
		}
		else {
			return null;
		}
	}

    public function cleanSkuImg($sku,$endSku,$cpath=""){
        $imgSku = '';
        if ( substr( $sku, strlen( $sku ) - strlen( $endSku ) ) === $endSku )
        {
            $dg_suffix_index = strrpos($sku, $endSku);
            $imgSku = substr_replace($sku, "", $endSku, 2) . ".jpg";
        }
        else
        {
            $imgSku = $sku . ".jpg";
        }

        $cpath .= $imgSku;

        $imgSize = getimagesize($cpath);

        if ($imgSize === false) {
            $imgSku = $sku . ".jpg";
        }

        return $imgSku;
    }
}


