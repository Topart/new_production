<?php

class Springbot_Util_Caller
{
	public $class;
	public $line;
	public $method;
	public $call_type;

	private static $_trace;

	public function __construct($class, $method, $type, $line)
	{
		$this->class = $class;
		$this->method = $method;
		$this->call_type = $type;
		$this->line = $line;
	}

	/**
	 * This weird little method returns an instance of this class
	 * populated with data retrived from the backtrace inspection.
	 *
	 * @param $depth int
	 * @return Springbot_Util_Caller
	 */
	public static function find($depth = 1)
	{
		self::$_trace = debug_backtrace();

		// Get the class that is asking for who awoke it
		$caller = self::$_trace[$depth];
		$called = self::_getCalledAt($depth);

		return new Springbot_Util_Caller(
			self::_safeGet($caller, 'class'),
			self::_safeGet($caller, 'function'),
			self::_safeGet($caller, 'type'),
			self::_safeGet($called, 'line')
		);
	}

	protected static function _safeGet($array, $key)
	{
		return isset($array[$key]) ? $array[$key] : null;
	}

	private static function _getCalledAt($depth)
	{
		return self::_safeGet(self::$_trace, $depth - 1);
	}

}
