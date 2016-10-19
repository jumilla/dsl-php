<?php

namespace Spellu\Dsl;

interface Expression
{
	/**
	 * @return Thunk
	 */
	public function evaluate();

	/**
	 * @return Thunk
	 */
	public function __invoke();

	/**
	 * @param string $method
	 * @param array $args
	 * @return Spellu\Dsl\Expression
	 */
	public function __call($method, $args);
}

class ExpressionCall implements Expression
{
	/**
	 * @param Funcuit $funcuit
	 * @param Spellu\Dsl\Action | Closure $callable
	 * @param array(Spellu\Dsl\Thunk) $thunks
	 */
	public function __construct(Funcuit $funcuit, callable $callable, array $thunks)
	{
		$this->funcuit = $funcuit;
		$this->callable = $callable;
		$this->bindedThunks = $thunks;
	}

	/**
	 * @return Thunk
	 */
	public function evaluate()
	{
		return call_user_func_array($this->callable, array_merge([$this->funcuit], $this->bindedThunks));
	}

	/**
	 * @return Thunk
	 */
	public function __invoke()
	{
		return $this->evaluate();
	}

	/**
	 * @param string $method
	 * @param array $args
	 * @return Spellu\Dsl\Expression
	 */
	public function __call($method, $arguments)
	{
		$expression = $this->funcuit->expressionA($method, $arguments);
		return new ExpressionBind($this->funcuit, [$this, $expression]);
	}
}

abstract class Combination implements Expression
{
	/**
	 * @param Funcuit $funcuit
	 * @param array(Spellu\Dsl\Expression) $expressions
	 */
	public function __construct(Funcuit $funcuit, array $expressions, array $arguments = null)
	{
		$this->funcuit = $funcuit;
		$this->expressions = $expressions;
		$this->arguments = $arguments;
	}

	/**
	 * @return Thunk
	 */
	public function __invoke()
	{
		return $this->evaluate();
	}

	/**
	 * @param string $method
	 * @param array $args
	 * @return Spellu\Dsl\Expression
	 */
	public function __call($method, $arguments)
	{
		$expression = $this->funcuit->expressionA($method, $arguments);
		return new static($this->funcuit, array_merge($this->expressions, [$expression]));
	}
}

/**
 * bind(fa, fb, ..., fn) => fa(); fb(); ...; return fn()
 */
class ExpressionBind extends Combination
{
	/**
	 * @return Thunk
	 */
	public function evaluate()
	{
		$result = Thunk::nothing();

		$thunks = map($this->expressions, function ($v) { return thunk($v); });

		foreach ($thunks as $thunk) {
			$result = thunk(toValue($thunk));
			if ($result->isFailure()) return $result;
		}

		return $result;
	}
}

/**
 * combine(fa, fb, ..., fn) => return fb(fa(...fn(x))
 * TODO Expressionに評価時パラメーターをサポートしてから、このロジックを実装する。
 */
class ExpressionCombine extends Combination
{
	/**
	 * @return Thunk
	 */
	public function evaluate()
	{
		$thunks = map($this->expressions, function ($v) { return thunk($v); });

		$result = thunk(toValue($this->arguments[0]));

		foreach ($thunks as $thunk) {
			$value = $arg($this, $value);
			if ($value->isFailure()) return $value;
		}

		return $result;
	}
}

/**
 * concat(fa, fb, ..., fn) => return [fa(), fb(), ..., fn()]
 */
class ExpressionConcat extends Combination
{
	/**
	 * @return Thunk
	 */
	public function evaluate()
	{
		$returnValues = [];

		$thunks = map($this->expressions, function ($v) { return thunk($v); });

		foreach ($thunks as $thunk) {
			$result = thunk(toValue($thunk));
			if ($result->isFailure()) return $result;

			$returnValues[] = $result;
		}

		return $returnValues;
	}
}

/**
 * or(fa, fb, ..., fn) => return fa() ?? fb() ... ?? fn() ※左結合
 */
class ExpressionOr extends Combination
{
	/**
	 * @return Thunk
	 */
	public function evaluate()
	{
		$state = $this->funcuit->__funcuit_save();
		$result = Thunk::nothing();

		$thunks = map($this->expressions, function ($v) { return thunk($v); });

		foreach ($thunks as $thunk) {
			$result = thunk(toValue($tunk));
			if (! $value->isFailure()) return $result;
			$this->funcuit->funcuit_restore($state);
		}

		return $result;
	}
}

trait ExpressionPool
{

}
