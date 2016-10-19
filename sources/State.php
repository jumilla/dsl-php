<?php

namespace Spellu\Dsl;

class State implements Funcuit
{
	use ActionPool;
	use ExpressionPool;

	public function __construct()
	{
		$this->op = new Control($this);

		$this->define('get', '() -> Any', function (State $state) {
			return $state->value;
		});

		$this->define('put', 'Any -> ()', function (State $state, $value) {
			$state->value = $value;
			return Thunk::void();
		});

		$this->define('modify', 'Any -> Any', function (State $state, $value) {
			$old = $state->value;
			$state->value = $value;
			return $old;
		});
	}

	public function __call($method, $args)
	{
		return $this->expressionA($method, $args);
	}

	public function __funcuit_save()
	{
		return null;
	}

	public function __funcuit_restore($state)
	{
	}

	public function evaluate($thunk)
	{
		while ($thunk->isExpression()) {
			$thunk = thunk($thunk->evaluate($this));
		}

		return $thunk;
	}

	public function run(Expression $expression, $initialValue)
	{
		$this->value = $initialValue;

		return $this->evaluate(thunk($expression))->value();
	}
}
