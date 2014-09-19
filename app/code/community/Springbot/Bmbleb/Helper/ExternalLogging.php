<?php
class Springbot_Bmbleb_Helper_ExternalLogging
{
	public function visibility($msg,$storeID='',$storeURL='',$pri='5')
	{
		Mage::log('Springbot: '.$msg);
		$uri = $this->fetchConfigVariable('api_url','https://api.springbot.com/').'api/logs';
		$filterChr			=array('"','{','}'.'[',']',':');

		try {
			$rawJSON='{"logs" :{"'.$storeID.'":{"store_id":"'.$storeID.'",'
				.'"event_time":"' .date("Y-m-d H:i:s ".'-0500').'",'
				.'"store_url":"'  .$storeURL.'",'
				.'"priority":"'   .$pri.'",'
				.'"description":"'.str_replace($filterChr,' ',$msg).'"'
				.'}}}';
			$client = new Varien_Http_Client($uri);
			$client->setRawData($rawJSON);
			$response			= json_decode($client->request('POST'),true);
			if ($response['status']=='error') {
				Mage::log('Springbot: '
					.'Post to->'.$url.' '
					.'JSON Buffer->'.$rawJSON.' '
					.'Response->'.implode(' ',$response));
			}
		} catch (Exception $e) {
			Mage::log('Springbot: '.$e->getMessage());
		}
		return;
	}

	public function log($toSend, $storeID, $priority = '5')
	{
		$uri = $this->fetchConfigVariable('api_url','https://api.springbot.com/').'api/logs';

		try {
			$client = new Varien_Http_Client($uri);
			$client->setRawData($this->encodePayload($toSend, $storeID));
			$response = json_decode($client->request('POST'),true);

			if ($response['status']=='error') {
				Mage::log('Springbot: '
					.'Post to->'.$url.' '
					.'JSON Buffer->'.$rawJSON.' '
					.'Response->'.implode(' ',$response));
			}
		} catch (Exception $e) {
			Mage::log('Springbot: '.$e->getMessage());
		}
		return;
	}

	public function encodePayload($array, $storeId, $priority = '5', $type = 'logs')
	{
		$array['event_time'] = date("Y-m-d H:i:s ".'-0500');
		$array['priority'] = $priority;
		$obj = new stdClass;
		$str = new stdClass;
		$str->$storeId = $array;
		$obj->$type = $str;
		return json_encode($obj);
	}

	private function fetchConfigVariable($varName,$defaultValue='')
	{
		$rtnValue = Mage::getStoreConfig('springbot/config/' . $varName);
		return !empty($rtnValue) ? $rtnValue : $defaultValue;
	}
}
