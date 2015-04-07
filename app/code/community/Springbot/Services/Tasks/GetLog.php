<?php

class Springbot_Services_Tasks_GetLog extends Springbot_Services
{
	public function run()
	{
		$buffer = Mage::helper('combine')->getLogContents($this->getLogName());

		$logData = array(
			'logs' => array(
				array(
					'store_id' => $this->getSpringbotStoreId(),
					'description' => $buffer,
				),
			),
		);

		Mage::getModel('combine/api')->call('logs', json_encode($logData), false);

		if (isset($result['status'])) {
			if ($result['status']==self::SUCCESSFUL_RESPONSE) {
				Springbot_Log::harvest('['.__METHOD__.'] was successfully delivered');
			}
			else {
				Springbot_Log::harvest('['.__METHOD__.'] delivery failed ->'.$result['status']);
			}
		}
		return true;
	}
}
