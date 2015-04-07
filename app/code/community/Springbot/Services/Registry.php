<?php

class Springbot_Services_Registry
{
	public static function getInstance($method)
	{
		$classname = self::_constantize($method);
		Springbot_Log::debug("Creating instance of $classname");

		if(class_exists($classname)) {
			return new $classname();
		} else {
			return false;
		}
	}

	public static function parseOpts($opts)
	{
		Springbot_Log::debug("Parsing args");

		if(isset($opts['s'])) {
			$args['store_id'] = $opts['s'];
		}

		if(isset($opts['i'])) {
			list($start, $stop) = array_pad(explode(':', $opts['i']), 2, null);
			$args['entity_id'] = $start;
			$args['start_id'] = $start;
			$args['stop_id'] = $stop;
		}

		if(isset($opts['h'])) {
			$args['halt_command'] = $opts['h'];
		}

		if(isset($opts['c'])) {
			$args['class'] = $opts['c'];
		}

		if(isset($opts['v'])) {
			$args['version'] = $opts['v'];
			$args['harvest_id'] = $opts['v'];
		}

		if(isset($opts['r'])) {
			$args['redirect_ids'] = $opts['r'];
		}

		if(isset($opts['j'])) {
			$args['json'] = $opts['j'];
		}

		if(isset($opts['n'])) {
			$args['filename'] = $opts['n'];
		}

		if(isset($opts['m'])) {
			$args['post_method'] = $opts['m'];
		}

		if(isset($opts['p'])) {
			$args['pid'] = $opts['p'];
		}

		$args['delete'] = isset($opts['d']);
		$args['force'] = isset($opts['f']);
		$args['is_foreman'] = isset($opts['o']);

		return $args;
	}

	/**
	 * Take a colon-separated string and turns it into a classname
	 * in the Springbot_Services_ namespace
	 *
	 * @param string $method
	 * @return string
	 */
	private static function _constantize($method)
	{
		$mods = array_map('ucfirst', explode(':', $method));
		return 'Springbot_Services_' . implode('_', $mods);
	}
}
