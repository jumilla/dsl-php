<?php

namespace Spellu\Dsl;

class Control
{
	protected $funcuit;

	public function __construct(Funcuit $funcuit)
	{
		$this->funcuit = $funcuit;
	}

	/**
	 * thunkの前後に式を追加する。
	 * fa(); fb(); fc(); fd()...
	 */
	public function bind(...$expressions)
	{
		return new ExpressionBind($this->funcuit, $expressions);
	}

	/**
	 * combine(fa, fb, ...) => ...fb(fa(x))
	 */
	public function combine(...$expressions)
	{
		return new ExpressionCombine($this->funcuit, $expressions);
	}

	/**
	 * concat(fa, fb, ...) => [fa(x), fb(x), ...]
	 *
	 * @param array(object(Action)) $expressions
	 * @return Thunk (array|null)
	 */
	public function concat(...$expressions)
	{
		return new ExpressionConcat($this->funcuit, $expressions);
	}

	/**
	 * or(fa, fb, ...) => fa(x) ?? fb(x) ...
	 *
	 * @param array(object(Action)) $expressions
	 * @return Thunk (any|null)
	 */
	public function or(...$expressions)
	{
		return new ExpressionOr($this->funcuit, $expressions);
		return function ($state) use ($left, $right) {
			$saved = clone $state;

			$value = $left($this, $state);
			if (!$value->isFailure()) return $value;

			$state = $saved;
			return $right($this, $state);
		};
	}
}
