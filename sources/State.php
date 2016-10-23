<?php

namespace Spellu\Dsl;

class State extends Funcuit
{
	use ActionPool;
	use ExpressionPool;

	public $op;

	public $value;

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

	public function runState(Expression $expression, $initialValue)
	{
		$this->value = $initialValue;

		return $this->evaluate(thunk($expression));
	}
}
