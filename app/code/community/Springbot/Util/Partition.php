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
		return $this->start . ':' . $this->stop;
	}
}
