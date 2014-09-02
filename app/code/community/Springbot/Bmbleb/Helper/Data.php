<?php


class Springbot_Bmbleb_Helper_Data extends Mage_Core_Helper_Abstract
{

	/**
	 * Helper method to only write debug logging when debug mode is enabled 
	 * @return string|number
	 */
	function debugLog($message = ''){
		if (Mage::getStoreConfig('bmbleb/config/debug_enabled',Mage::app()->getStore())){
			Mage::log($message);
		}
	}
	
	/**
	 * Helper method to wrap requests for current time so we can adjust for different magento versions
	 * @return string|number
	 */
	function getTime(){
		if (function_exists('now')){
			return now();	// test just incase
		} else if (method_exists('Varien_Date','now')){
			return Varien_Date::now();
		}
		// fallback
		return time();		
	}
	
	function getDashboardInsightsStats(){
		return $this->getSalesByAttributeCode('bmbleb_gender', 120);
	}
	
	function getSalesByAttributeCode($attributeCode = 'bmbmeb_age', $numberDaysInRange = 120){
		/*
		 * Sales by Age Last 60 Days.  Pie chart.  Use the age groups that Rapleaf gives us (whatever's in the data already.)  Calculate the total sales by customers of each age group over the last 60 days and put that value into a pie chart.  I guess you'll have to sum them all and determine a percentage for each so that they total 100%.
		*/
		$stats = array();
		// load the table data with a custom query
		$resource = Mage::getSingleton('core/resource');
		$readConnection = $resource->getConnection('core_read');
		
		// setup date range
		$now = strtotime(Mage::helper('bmbleb')->getTime());
		$format = 'Y-m-d H:i:s';
		$currentEndDate = date($format, $now);
		$previousStartDate = date($format, strtotime("- ".($numberDaysInRange)." days", $now));
		
		$attribIdSelect = "SELECT a.attribute_id from " . $resource->getTableName('eav_attribute') . " a WHERE a.attribute_code = '" . $attributeCode . "'";
		$query = "SELECT v.attribute_id, v.value, COUNT(o.entity_id) AS orders_count, SUM((IFNULL(o.base_grand_total, 0) - IFNULL(o.base_total_canceled, 0)) * IFNULL(o.base_to_global_rate, 0)) AS total_income_amount FROM ".$resource->getTableName('sales_flat_order')." AS o left join " . $resource->getTableName('customer_entity_varchar') . " v on o.customer_id = v.entity_id WHERE (o.state NOT IN ('pending_payment', 'new')) and o.created_at between '".$previousStartDate."' and '".$currentEndDate."' and v.attribute_id in (" . $attribIdSelect . ") group by v.value, v.attribute_id order by v.attribute_id";
		$results = $readConnection->fetchAll($query);
		foreach ($results as $row){
			$attribValue = $row['value'];
			if (!empty($attribValue) && !empty($attribValue)){
				$stat = new Varien_Object();
				$stat->setData(array('label' => $attribValue, 'value' => $row['total_income_amount']));
				$stats[] = $stat;
			}
		}
		
		return $stats;
	}
	
	function getStatsSocialNetworkLinks(){
		$stats = array();
		
		$collection = Mage::getResourceModel('customer/customer_collection');
		$totalCustomers = $collection->count();

		// NOTE: the OR was hard to track down. The key was the 'left' join - needed to pass through a null for the second parameter
		$collection = Mage::getResourceModel('customer/customer_collection')
			->addAttributeToFilter(
					array(
						array('attribute' => 'bmbleb_twitter', 'notnull'=>true),
						array('attribute' => 'bmbleb_facebook', 'notnull'=>true),
						array('attribute' => 'bmbleb_linkedin', 'notnull'=>true)
					),
					NULL,
					'left'
			);
		$hasOneOrMore = $collection->count();
		
		$collection = Mage::getResourceModel('customer/customer_collection')
			->addAttributeToFilter('bmbleb_twitter', array('notnull'=>true))
			->addAttributeToFilter('bmbleb_facebook', array('notnull'=>true))
			->addAttributeToFilter('bmbleb_linkedin', array('notnull'=>true));
		$hasThree = $collection->count();
		
		$hasNone = $totalCustomers - $hasOneOrMore;	// no links = total - one or more
		$hasOneOrTwo = $hasOneOrMore - $hasThree;	// one or two = one or more - has three
		
		$stat = new Varien_Object();
		$stat->setData(array('label' => 'No Links', 'value' => $hasNone));
		$stats[] = $stat;
		
		$stat = new Varien_Object();
		$stat->setData(array('label' => '1 or 2 Links', 'value' => $hasOneOrTwo));
		$stats[] = $stat;
				
		$stat = new Varien_Object();
		$stat->setData(array('label' => '3 Links', 'value' => $hasThree, 'selected' => true));
		$stats[] = $stat;
				
		return $stats;
	}
	
	function getInsightsStats(){
		$collection = Mage::getResourceModel('customer/customer_collection');
		$totalCustomers = $collection->count();

		$collection = Mage::getResourceModel('customer/customer_collection')
		->addAttributeToFilter('bmbleb_facebook', array('notnull'=>true));
		$totalFacebook = $collection->count();
		
		$collection = Mage::getResourceModel('customer/customer_collection')
		->addAttributeToFilter('bmbleb_twitter', array('notnull'=>true));
		$totalTwitter = $collection->count();
		
		$collection = Mage::getResourceModel('customer/customer_collection')
		->addAttributeToFilter('bmbleb_linkedin', array('notnull'=>true));
		$totalLinkedIn = $collection->count();
		
		$stats = new Varien_Object();
		$stats->setTotal($totalCustomers);
		$stats->setFacebook($totalFacebook / $totalCustomers * 100);
		$stats->setTwitter($totalTwitter / $totalCustomers * 100);
		$stats->setLinkedIn($totalLinkedIn / $totalCustomers * 100);
		
		return $stats;
	}
	
	
	
}