<?php
class GoDataFeed_Services_Model_Method_OrderSync extends Mage_Payment_Model_Method_Cc
{
	public function validate() {
		return true;
	}

	public function getCode() {
		return "OrderSync";
	}
}


