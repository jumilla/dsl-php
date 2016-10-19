<?php

namespace Spellu\Dsl;

class Thunk
{
	private static $void;
	private static $nothing;

	public static function void()
	{
		if (self::$void === null) {
			self::$void = new static([]);
		}
		return self::$void;
	}

	public static function nothing()
	{
		if (self::$nothing === null) {
			self::$nothing = new static(null);
		}
		return self::$nothing;
	}

	protected $data;	// expr or value

	public function __construct($data)
	{
		$this->data = $data;
	}

	public function isExpression()
	{
		return $this->data instanceof Expression;
	}

	public function isValue()
	{
		return !($this->data instanceof Expression);
	}

	public function isFailure()
	{
		if ($this->isExpression()) return null; // or 例外送出

		return $this->data === null ||
			($this->data instanceof Failable && $this->data->isFailure());
	}

	public function evaluate()
	{
		if ($this->isExpression()) {
			$this->data = $this->data->evaluate();
		}

		return $this->data;
	}

	public function value()
	{
		if ($this->isExpression())
			return null; // or 例外送出
		else
			return $this->data;
	}
}
