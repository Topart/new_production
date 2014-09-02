<?php

interface Springbot_Combine_Model_Harvester
{
	public function getCollection();

	public function setCollection(Varien_Data_Collection $collection);

	public function harvest();

	public function step($args);

	public function parse($model);

	public function loadMageModel($entityId);
}
