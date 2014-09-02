<?php

class Springbot_Combine_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function formatDateTime($date = null)
	{
		$_date = new DateTime($date, new DateTimeZone('UTC'));
		return $_date->format(DateTime::ATOM);
	}

	public function getStoreGuid($storeId)
	{
		$guid = Mage::getStoreConfig('springbot/config/store_guid_' . $storeId);
		if (empty($guid)) {
			$charid = strtoupper(md5(uniqid(rand(), true)));
			$guid = substr($charid, 0, 8).'-'
				.substr($charid, 8, 4).'-'
				.substr($charid,12, 4).'-'
				.substr($charid,16, 4).'-'
				.substr($charid,20,12);
		}
		return $guid;
	}

	public function apiPostWrapped($model, $struct, $arrayWrap = false)
	{
		if($arrayWrap) {
			$struct = array($struct);
		}
		$api = Mage::getModel('combine/api');
		$payload = $api->wrap($model, $struct);
		return $api->reinit()->call($model, $payload);
	}

	public function checkCredentials($email = null, $password = null)
	{
		$return = array('valid' => false);

		try {
			$this->requestSecurityToken($email, $password, true);
			$return['valid'] = true;
		} catch (Exception $e) {
			$return['message'] = $e->getMessage();
		}
		return $return;
	}

	public function requestSecurityToken($email = null, $password = null, $force = false)
	{
		$token = Mage::getStoreConfig('springbot/config/security_token');
		if($token && !$force) {
			return $token;
		}

		$payload = $this->_resolvePassword($email, $password);

		$response = Mage::getModel('combine/api')->call('registration/login', json_encode($payload), false);

		if(!isset($response['token'])) {
			throw new Exception($response['message']);
		}
		return $response['token'];
	}

	protected function _resolvePassword($email = null, $password = null)
	{
		if(is_null($email) || is_null($password)) {
			$payload = array(
				'user_id' => Mage::getStoreConfig('springbot/config/account_email'),
				'password' => Mage::helper('core')->decrypt(Mage::getStoreConfig('springbot/config/account_password')),
			);
		} else {
			$payload = array(
				'user_id' => $email,
				'password' => $password,
			);
		}
		return $payload;
	}

	public function doSendQuote($json)
	{
		$obj = json_decode($json);
		$items = sha1(json_encode((isset($obj->line_items)) ? $obj->line_items : array()));

		if(strcmp(Mage::getSingleton('core/session')->getSpringbotPostedQuoteItems(), $items) !== 0) {
			Mage::getSingleton('core/session')->setSpringbotPostedQuoteItems($items);
			return true;
		} else {
			return false;
		}
	}

	public function escapeShell($arg)
	{
		if(function_exists('escapeshellarg')) {
			return escapeshellarg($arg);
		} else {
			return "'" . str_replace("'", "'\"'\"'", $arg) . "'";
		}
	}

	public function getMicroTime()
	{
		$mtime = explode(" ",microtime());
		return $mtime[1] + $mtime[0];
	}

	public function isJson($string)
	{
		return is_string($string) && json_decode($string) != null;
	}

	public function getLastCategoryId()
	{
		$this->setLastCategoryId();
		return Mage::getSingleton('core/session')->getSpringbotLastCategoryId();
	}

	public function setLastCategoryId()
	{
		$product  = Mage::registry('current_product');
		$category = Mage::registry('current_category');

		if($categoryId = $this->resolveCategoryId($category, $product)) {
			Mage::getSingleton('core/session')->setSpringbotLastCategoryId($categoryId);
		}
	}

	/**
	 * Resolve category id to set in session
	 *
	 * We are handling multiple states:
	 * cat && prod => check if cat makes sense
	 * prod => pop cat id from prod
	 * cat => use cat id
	 * !cat && !prod => use cached
	 *
	 * @param Mage_Catalog_Model_Category $category
	 * @param Mage_Catalog_Model_Product $product
	 * @return int|null
	 */
	public function resolveCategoryId($category, $product)
	{
		$categoryId = null;
		if((isset($product) || isset($category)))
		{
			if(isset($product) && isset($category))
			{
				$productCatIds = $product->getCategoryIds();
				$categoryId    = $category->getId();

				if(!in_array($categoryId, $productCatIds))
				{
					$categoryId = array_pop($productCatIds);
				}
			}
			else if(isset($product))
			{
				$productCatIds = $product->getCategoryIds();
				$categoryId = array_pop($productCatIds);
			}
			else if (isset($category))
			{
				$categoryId = $category->getId();
			}
		}
		return $categoryId;
	}

	public function checkCategoryIdSanity($categoryId, $product)
	{
		if(!$product instanceof Varien_Object) {
			$product = Mage::getModel('catalog/product')->load($product);
		}
		return $this->resolveCategoryId(
			new Varien_Object(array('id' => $categoryId)),
			$product
		);
	}

	public function getSpringbotErrorLog()
	{
		return Mage::getBaseDir('var') . DS . 'log' . DS . Springbot_Log::ERRFILE;
	}

	public function getSpringbotLog()
	{
		return Mage::getBaseDir('var') . DS . 'log' . DS . Springbot_Log::LOGFILE;
	}

	public function isEmpty($obj)
	{
		return count((array) $obj) == 0;
	}

	public function nohup()
	{
		return Mage::getStoreConfig('springbot/advanced/nohup') ? 'nohup' : '';
	}

	public function nice()
	{
		return Mage::getStoreConfig('springbot/advanced/nice') ? 'nice' : '';
	}

	public function getLogContents($logName)
	{
		$maxRecSize = 65536;
		if (empty($logName)) {
			$fullFilename = Mage::getBaseDir('log') . DS . Springbot_Log::LOGFILE;
		} elseif (strpos($logName, '/') === 0){
			$fullFilename = $logName;
		} else {
			$fullFilename = Mage::getBaseDir('log') . '/' . $logName;
		}

		$buffer = '';
		if(file_exists($fullFilename)) {
			if (($fHandle = fopen($fullFilename, 'r')) !== FALSE) {
				$fSize  = filesize($fullFilename)/1024;
				if ($fSize > 32) {
					fseek($fHandle, 1024*($fSize-32));
				}
				while (!feof($fHandle)) {
					$buffer .= fgets($fHandle,$maxRecSize) . ' ';
				}
				fclose ($fHandle);
			} else {
				$buffer='Open failed on '.$fullFilename;
			}
		}
		return $buffer;
	}
}
