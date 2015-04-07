<?php

class Springbot_Util_Partition
{
	public $start;
	public $stop;

	public function __construct($start, $stop)
	{
		$this->start = $start;
		$this->stop = $stop;
	}

	public function fromStart()
	{
		return $this->start . ':';
	}

	public function __toString()
	{
		// We are non-inclusive from the start
		return ($this->start - 1) . ':' . $this->stop;
	}
}
