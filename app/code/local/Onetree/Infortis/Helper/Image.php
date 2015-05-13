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
                $cpath = $cloudFontBaseUrl .DS. $version . DS;
		$imgSku = $this->cleanSkuImg($sku,'DG',$cpath);
		$url = $cloudFontBaseUrl .DS. $version . DS. $imgSku;
                
                /*JP: Fix for ticket 257 Start*/
                $size = false;//getimagesize($url);
                
                if(!$size){
                    $url = str_replace("DG.", ".", $url);
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
			$imgSku = substr_replace($sku, "", $endSku, 2) . ".jpg";
		}
		else
		{
			$imgSku = $sku . ".jpg";
		}
                
                $cpath .= $imgSku;
                
                $imgSize = false;//getimagesize($cpath);
                
                if ($imgSize === false) {
                    $imgSku = $sku . ".jpg";
                }
                
		return $imgSku;
	}
}
