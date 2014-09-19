<?php

class Springbot_Services_Post_Json extends Springbot_Services_Post
{
	public function run()
	{
		$file = Mage::getModel('combine/file_io');
		$filename = $this->getFilename();

		if($file->exists($filename)) {
			$quoteJson = $file->read($filename);
			$file->delete();
		}

		Mage::helper('combine')->apiPostWrapped($this->getPostMethod(), json_decode($quoteJson));
	}
}
