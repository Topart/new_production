<?php

class Onetree_Infortis_Helper_Image extends Infortis_Infortis_Helper_Image
{
	/**
	 * Get image URL of the given product
	 *
	 * @param Mage_Catalog_Model_Product	$product		Product
	 * @param int							$w				Image width
	 * @param int							$h				Image height
	 * @param string						$imgVersion		Image version: image, small_image, thumbnail
	 * @param mixed							$file			Specific file
	 * @return string
	 */
	public function getImg($product, $w, $h, $imgVersion='image', $file=NULL)
	{
		$cloudFontBaseUrl = 'http://d3odr912zwpuhm.cloudfront.net';

		if($product instanceof Mage_Catalog_Model_Product){
			$sku = $product->getData('sku');
		}else{
			$sku = $product;
		}

		switch($imgVersion){

			case 'small_image':
				$version = 'small_images';
			break;

			case 'thumbnail':
				$version = 'thumbnail_images';
			break;
			default:
				$version = 'large_images';
			break;
		}

		if($file =="alternative")
			$sku = $sku."_Alternative";


                $cpath = $cloudFontBaseUrl .'/'. $version . '/';
		$imgSku = $this->cleanSkuImg($sku,'/',$cpath);
		$url = $cloudFontBaseUrl .'/'. $version . '/'. $imgSku;
                
                /*JP: Fix for ticket 257 Start*/
                $size = false;//getimagesize($url);
                
                if(!$size){
                    $url = str_replace("/.", ".", $url);
                }
                /*JP: Fix for ticket 257 End*/
                
		return $url;
		if ($h <= 0)
		{
			$url = Mage::helper('catalog/image')
				->init($product, $imgVersion, $file)
				->constrainOnly(true)
				->keepAspectRatio(true)
				->keepFrame(false)
				//->setQuality(90)
				->resize($w);
		}
		else
		{
			$url = Mage::helper('catalog/image')
				->init($product, $imgVersion, $file)
				->resize($w, $h);
		}
		return $url;
	}
	public function cleanSkuImg($sku,$endSku,$cpath=""){
		$imgSku = '';
		if ( substr( $sku, strlen( $sku ) - strlen( $endSku ) ) === $endSku )
		{
			$dg_suffix_index = strrpos($sku, $endSku);
			$imgSku = strtoupper(substr_replace($sku, "", $endSku, 2)) . ".jpg";
		}
		else
		{
			$imgSku = strtoupper($sku) . ".jpg";
		}
                
                $cpath .= $imgSku;
                
                $imgSize = false;//getimagesize($cpath);
                
                if ($imgSize === false) {
                    $imgSku = $sku . ".jpg";
                }
                
		return $imgSku;
	}
	function isMobile() {
		return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
	}

	function file_exists_remote($url) {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_NOBODY, true);
		//Check connection only
		$result = curl_exec($curl);
		//Actual request
		$ret = false;
		if ($result !== false) {
			$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			//Check HTTP status code
			if ($statusCode == 200) {
				$ret = true;
			}
		}
		curl_close($curl);
		return $ret;
	}

}
