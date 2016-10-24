<?php

namespace Spellu\Dsl;

abstract class Expression implements Evaluable
{
	/**
	 * @var Spellu\Dsl\Funcuit
	 */
	protected $funcuit;

	/**
	 * @var array
	 */
	protected $reducers = [];

	/**
	 * @param Spellu\Dsl\Funcuit $funcuit
	 */
	public function __construct(Funcuit $funcuit)
	{
		$this->funcuit = $funcuit;
	}

	/**
	 * @param string $name
	 * @param array $args
	 * @return Spellu\Dsl\Evaluable
	 */
	public function bind($name, $arguments)
	{
		$expression = $this->funcuit->actionPool()->_expression($name, $arguments);
		return new ExpressionConcat($this->funcuit, [$this, $expression]);
	}

	/**
	 * @param callable $reducer
	 * @return Spellu\Dsl\Evaluable
	 */
	public function reduce(callable $reducer)
	{
		$this->reducers[] = $reducer;
		return $this;
	}

	/**
	 * @param int $offset
	 * @return Spellu\Dsl\Evaluable
	 */
	public function reduceN($offset)
	{
		$this->reducers[] = function ($result) use ($offset) {
			if (!is_array($result)) throw new DslException('reducer: reduceN: result is not array.');
			return $result[$offset];
		};
		return $this;
	}

	/**
	 * @return Spellu\Dsl\Evaluable
	 */
	public function reduceL()
	{
		$this->reducers[] = function ($result) {
			if (!is_array($result)) throw new DslException('reducer: reduceL: result is not array.');
			return current($result);
		};
		return $this;
	}

	/**
	 * @return Spellu\Dsl\Evaluable
	 */
	public function reduceR()
	{
		$this->reducers[] = function ($result) {
			if (!is_array($result)) throw new DslException('reducer: reduceR: result is not array.');
			return end($result);
		};
		return $this;
	}

	/**
	 * @param string $name
	 * @param array $args
	 * @return Spellu\Dsl\Evaluable
	 */
	public function __call($name, $arguments)
	{
		if (substr($name, 0, 6) === 'reduce') {
			call_user_func_array([$this->funcuit, $name], array_merge([$this], $arguments));
			return $this;
		}
		if ($name[0] === '_') $name = substr($name, 1);
		return $this->bind($name, $arguments);
	}

	/**
	 * @return Spellu\Dsl\Thunk
	 */
	public function __invoke()
	{
		$result = $this->evaluate();

		if (!$result->isFailure() && $this->reducers) {
			$value = $result->value();
			foreach ($this->reducers as $reducer) {
				$value = $reducer($value);
			}
			$result = thunk($value);
		}

		return $result;
	}

	/**
	 * @return Spellu\Dsl\Thunk
	 */
	abstract protected function evaluate();

	/**
	 * @return string
	 */
	public function __toString()
	{
		return get_class($this);
	}
}

class ExpressionCall extends Expression
{
	protected $callable;

	protected $bindedThunks;

	/**
	 * @param Spellu\Dsl\Funcuit $funcuit
	 * @param Spellu\Dsl\Action | Closure $callable
	 * @param array(Spellu\Dsl\Thunk) $thunks
	 */
	public function __construct(Funcuit $funcuit, callable $callable, array $thunks)
	{
		parent::__construct($funcuit);
		$this->callable = $callable;
		$this->bindedThunks = $thunks;
	}

	/**
	 * @return Spellu\Dsl\Thunk
	 */
	protected function evaluate()
	{
		return thunk(call_user_func_array($this->callable,
			array_merge([$this->funcuit], $this->arguments())
		));
	}

	/**
	 * @return array(Spellu\Dsl\Thunk)
	 */
	protected function arguments()
	{
		return $this->bindedThunks;
	}
}

abstract class ExpressionUnary extends Expression
{
	/**
	 * @param Spellu\Dsl\Funcuit $funcuit
	 * @param Spellu\Dsl\Evaluable $expression
	 */
	public function __construct(Funcuit $funcuit, Evaluable $expression)
	{
		parent::__construct($funcuit);
		$this->expression = $expression;
	}
}

class ExpressionRepeat extends ExpressionUnary
{
	/**
	 * @return Spellu\Dsl\Thunk
	 */
	public function evaluate()
	{
		$returnValues = [];

		while (true) {
			$result = thunk(thunk($this->expression)->evaluate());
			if ($result->isFailure()) break;	// failureならループを抜ける
			$returnValues[] = $result->value();
		}

		// TODO many1() などに対応
//var_dump('count', count($returnValue));
//		if (count($returnValue) == 0) return Thunk::fail();

		return thunk($returnValues);
	}
}

abstract class Combination extends Expression
{
	/**
	 * @param Funcuit $funcuit
	 * @param array(Spellu\Dsl\Evaluable) $expressions
	 */
	public function __construct(Funcuit $funcuit, array $expressions)
	{
		parent::__construct($funcuit);
		$this->expressions = $expressions;
	}

	/**
	 * @param string $name
	 * @param array $args
	 * @return Spellu\Dsl\Evaluable
	 */
	public function bind($name, $arguments)
	{
		$expression = $this->funcuit->actionPool()->_expression($name, $arguments);
		return new static($this->funcuit, array_merge($this->expressions, [$expression]));
	}
}

/**
 * combine(fa, fb, ..., fn) => return fb(fa(...fn(x))
 * TODO Expressionに評価時パラメーターをサポートしてから、このロジックを実装する。
 */
class ExpressionCombine extends Combination
{
	/**
	 * TODO: 動的パラメーター
	 */
	public function argument($argument)
	{
		$this->argument = $argument;
		return $this;
	}

	/**
	 * @return Spellu\Dsl\Thunk
	 */
	protected function evaluate()
	{
		$thunks = map($this->expressions, function ($v) { return thunk($v); });

		$result = thunk($this->argument->evaluate());

		foreach ($thunks as $thunk) {
			$result = $arg($this, $result);
			if ($result->isFailure()) break;
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
	 * @return Spellu\Dsl\Thunk
	 */
	protected function evaluate()
	{
		$returnValues = [];

		$expressions = map($this->expressions, function ($v) { return thunk($v); });

		foreach ($expressions as $expression) {
			$result = thunk($expression->evaluate());
			if ($result->isFailure()) return $result;
			$returnValues[] = $result->value();
		}

		return thunk($returnValues);
	}
}

/**
 * choice(n)(fa, fb, ..., fn) => return [fa(), fb(), ..., fn()][n]
 */
class ExpressionChoice extends Combination
{
	protected $offset = -1;

	/**
	 * @return Spellu\Dsl\Thunk
	 */
	protected function evaluate()
	{
		$result = Thunk::fail();

		$expressions = map($this->expressions, function ($v) { return thunk($v); });

		$index = 0;
		foreach ($expressions as $expression) {
			$result = thunk($expression->evaluate());
			if ($result->isFailure()) break;
			if ($index == $this->offset) break;
		}

		return $result;
	}

	/**
	 * @param int $offset
	 * @return Spellu\Dsl\Combination
	 */
	public function offset($offset)
	{
		$this->offset = $offset;
		return $this;
	}
}

/**
 * or(fa, fb, ..., fn) => return fa() ?? fb() ... ?? fn() ※左結合
 */
class ExpressionOr extends Combination
{
	/**
	 * @return Spellu\Dsl\Thunk
	 */
	protected function evaluate()
	{
		$state = $this->funcuit->saveState();
		$result = Thunk::fail();

		$thunks = map($this->expressions, function ($v) { return thunk($v); });

		foreach ($thunks as $thunk) {
			$result = thunk($thunk->evaluate());
			if (! $result->isFailure()) break;
			$this->funcuit->restoreState($state);
		}

		return $result;
	}
}
