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
	protected $onSuccesses = [];

	/**
	 * @var array
	 */
	protected $onFailures = [];

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
	 * @param callable $closure
	 * @return Spellu\Dsl\Evaluable
	 */
	public function reduce(callable $closure)
	{
		$this->onSuccesses[] = $closure;
		return $this;
	}

	/**
	 * @param int $offset
	 * @return Spellu\Dsl\Evaluable
	 */
	public function reduceN($offset)
	{
		$this->onSuccesses[] = function ($result) use ($offset) {
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
		$this->onSuccesses[] = function ($result) {
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
		$this->onSuccesses[] = function ($result) {
			if (!is_array($result)) throw new DslException('reducer: reduceR: result is not array.');
			return end($result);
		};
		return $this;
	}

	/**
	 * @param callable $closure
	 * @return Spellu\Dsl\Evaluable
	 */
	public function onFailure(callable $closure)
	{
		$this->onFailures[] = $closure;
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

		if ($result->isFailure()) {
			if ($this->onFailures) {
				$value = $result->value();
				foreach ($this->onFailures as $closure) {
					$value = $closure($value);
				}
				$result = thunk($value);
			}
		}
		else {
			if ($this->onSuccesses) {
				$value = $result->value();
				foreach ($this->onSuccesses as $closure) {
					$value = $closure($value);
				}
				$result = thunk($value);
			}
		}

//echo 'result: ', dump($result->value()), PHP_EOL;

		return $result;
	}

	/**
	 * @return Spellu\Dsl\Thunk
	 */
	abstract protected function evaluate();

	/**
	 * @return string
	 */
	public function name()
	{
		return substr(get_class($this), strlen('Spellu\Dsl\Expression'));
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->name();
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

	/**
	 * @return string
	 */
	public function name()
	{
		return $this->callable instanceof Action ? $this->callable->name() : '#func#';
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->name().'('.dump($this->bindedThunks).')';
	}
}

abstract class ExpressionUnary extends Expression
{
	/**
	 * @var Spellu\Dsl\Evaluable
	 */
	protected $expression;

	/**
	 * @param Spellu\Dsl\Funcuit $funcuit
	 * @param Spellu\Dsl\Evaluable $expression
	 */
	public function __construct(Funcuit $funcuit, Evaluable $expression)
	{
		parent::__construct($funcuit);
		$this->expression = $expression;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->name().'('.(string)$this->expression.')';
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
//		if (count($returnValue) == 0) return Thunk::failure();

		return thunk($returnValues);
	}
}

abstract class Combination extends Expression
{
	/**
	 * @var array(Spellu\Dsl\Evaluable)
	 */
	protected $expressions;

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

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->name().'('.dump($this->expressions).')';
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
		$expressions = map($this->expressions, function ($v) { return thunk($v); });

		$results = [];
		foreach ($expressions as $expression) {
			$result = thunk($expression->evaluate());
			if ($result->isFailure()) return $result;
			$results[] = $result;
		}

		return $results[$this->offset] ?? Thunk::failure();
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
		$result = Thunk::failure();

		$thunks = map($this->expressions, function ($v) { return thunk($v); });

		foreach ($thunks as $thunk) {
			$result = thunk($thunk->evaluate());
			if (! $result->isFailure()) break;
			$this->funcuit->restoreState($state);
		}

		return $result;
	}
}
