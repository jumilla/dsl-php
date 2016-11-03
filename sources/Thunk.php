<?php

namespace Spellu\Dsl;

class Thunk
{
	/**
	 * @var Spellu\Dsl\Thunk
	 */
	private static $void;

	/**
	 * @var Spellu\Dsl\Thunk
	 */
	private static $fail;

	/**
	 * @return Spellu\Dsl\Thunk
	 */
	public static function void()
	{
		if (self::$void === null) {
			self::$void = new static([]);
		}
		return self::$void;
	}

	/**
	 * @return Spellu\Dsl\Thunk
	 */
	public static function fail()
	{
		if (self::$fail === null) {
			self::$fail = new static(null);
		}
		return self::$fail;
	}

	/**
	 * @var Spellu\Dsl\Expression | mixed
	 */
	protected $data;	// expr or value

	/**
	 * @param mixed
	 */
	public function __construct($data)
	{
		$this->data = $data;
	}

	/**
	 * @return bool
	 */
	public function isExpression()
	{
		return $this->data instanceof Expression;
	}

	/**
	 * @return bool
	 */
	public function isValue()
	{
		return !($this->data instanceof Expression);
	}

	/**
	 * @return bool
	 */
	public function isFailure()
	{
		if ($this->isExpression()) return null; // or 例外送出

		return $this->data === null ||
			($this->data instanceof Failable && $this->data->isFailure());
	}

	/**
	 * @return mixed
	 */
	function evaluate()
	{
		while ($this->isExpression()) {
			$result = ($this->data)();
			$this->data = $result->data;
			if ($this->isFailure()) break;
		}
		return $this->data;
	}

	/**
	 * @return Spellu\Dsl\Expression
	 */
	public function expression()
	{
		return $this->isExpression() ? $this->data : null;
	}

	/**
	 * @return mixed
	 */
	public function value()
	{
		return $this->isExpression() ? null : $this->data;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		if ($this->data === null) {
			return 'Nothing';
		}
		else if ($this->data === []) {
			return 'Void';
		}
		else if ($this->data instanceof Failable) {
			return "Failable()". get_class($this->data);
		}
		else if ($this->isExpression()) {
			return "Expression()". get_class($this->data);
		}
		else if (is_array($this->data)) {
			$v = array_reduce($this->data, function ($string, $element) {
				return $string . print_r($this->data, true);
			}, '[') . ']';
			return "Value({$v})";
		}
		else if (is_object($this->data) && !method_exists($this->data, '__toString')) {
			return 'Object(.*.)';
		}
		else if (is_string($this->data)) {
			return "Value('{$this->data}')";
		}
		else {
			return "Value({$this->data})";
		}

	}
}
