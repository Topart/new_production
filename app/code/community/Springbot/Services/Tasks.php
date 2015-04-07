<?php

class Springbot_Services_Tasks
{

	public static function makeTask($taskname, $params)
	{
		$className = self::_snakeToCamel($taskname);
		$fullClassName = "Springbot_Services_Tasks_{$className}";
		Springbot_Log::debug("Init {$fullClassName}");
		if (class_exists($fullClassName)) {
			$task = new $fullClassName();
			$task->setData($params);
			return $task;
		}
		else {
			return null;
		}
	}

	private static function _snakeToCamel($string)
	{
		$toReturn = '';
		foreach(explode('_', $string) as $word) {
			$toReturn .= ucfirst($word);
		}
		return $toReturn;
	}

}
