<?php

namespace Spellu\Dsl;

class Tuple
{
	protected static $void;

	public static function void()
	{
		if (static::$void === null) {
			static::$void = new static([]);
		}
		return static::$void;
	}

	public function __construct($values)
	{
		$this->values = $values;
	}
}
