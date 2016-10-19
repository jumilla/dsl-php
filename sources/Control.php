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
	 * many(fa) => return [fa(), fa(), fa(), ...]
	 *
	 * @param Spellu\Dsl\Expression $expression
	 * @return Spellu\Dsl\Expression
	 */
	public function many($expression)
	{
		return new ExpressionRepeat($this->funcuit, $expression);
	}

	/**
	 * many(fa) => return [fa(), fa(), fa(), ...]
	 *
	 * @param Spellu\Dsl\Expression $expression
	 * @return Spellu\Dsl\Expression
	 */
	public function many1($expression)
	{
		return (new ExpressionRepeat($this->funcuit, $expression))->least(1);
	}

	/**
	 * combine(fa, fb, ...) => ...fb(fa(x))
	 *
	 * @param array(Spellu\Dsl\Expression) $expressions
	 * @return Spellu\Dsl\Expression
	 */
	public function combine(...$expressions)
	{
		return new ExpressionCombine($this->funcuit, $expressions);
	}

	/**
	 * concat(fa, fb, ...) => [fa(x), fb(x), ...]
	 *
	 * @param array(Spellu\Dsl\Expression) $expressions
	 * @return Spellu\Dsl\Expression
	 */
	public function concat(...$expressions)
	{
		return new ExpressionConcat($this->funcuit, $expressions);
	}

	/**
	 * choice(n)(fa, fb, ..., fn) => return [fa(), fb(), ..., fn()][n]
	 *
	 * @param array(Spellu\Dsl\Expression) $expressions
	 * @return Spellu\Dsl\Expression
	 */
	public function choice($offset, ...$expressions)
	{
		return (new ExpressionChoice($this->funcuit, $expressions))->offset($offset);
	}

	/**
	 * and(fa, fb, ..., fn) => return [fa(), fb(), ..., fn()][-1]
	 *
	 * @param array(Spellu\Dsl\Expression) $expressions
	 * @return Spellu\Dsl\Expression
	 */
	public function and(...$expressions)
	{
		return (new ExpressionChoice($this->funcuit, $expressions));
	}

	/**
	 * and(fa, fb, ..., fn) => return [fa(), fb(), ..., fn()][0]
	 *
	 * @param array(Spellu\Dsl\Expression) $expressions
	 * @return Spellu\Dsl\Expression
	 */
	public function andL(...$expressions)
	{
		return (new ExpressionChoice($this->funcuit, $expressions))->offset(0);
	}

	/**
	 * and(fa, fb, ..., fn) => return [fa(), fb(), ..., fn()][-1]
	 *
	 * @param array(Spellu\Dsl\Expression) $expressions
	 * @return Spellu\Dsl\Expression
	 */
	public function andR(...$expressions)
	{
		return (new ExpressionChoice($this->funcuit, $expressions))->offset(count($expressions) - 1);
	}

	/**
	 * or(fa, fb, ..., fn) => return fa() ?? fb() ?? ... ?? fn()
	 *
	 * @param array(Spellu\Dsl\Expression) $expressions
	 * @return Spellu\Dsl\Expression
	 */
	public function or(...$expressions)
	{
		return new ExpressionOr($this->funcuit, $expressions);
	}

	public function trace(...$arguments)
	{
		return new ExpressionCall($this->funcuit, function (Funcuit $funcuit, $arguments) {
			foreach ($arguments as $argument) {
				echo print_r($argument, true);
			}
			echo PHP_EOL;
			return Thunk::void();
		}, [$arguments]);
	}
}
