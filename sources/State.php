<?php

namespace Spellu\Dsl;

class State extends Funcuit
{
	public $value;

	public function __construct()
	{
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

	public function runState(Expression $expression, $initialValue)
	{
		$this->value = $initialValue;

		return $this->evaluate(thunk($expression));
	}

	/**
	 * @return mixed
	 */
	public function saveState()
	{
		return $this->value;
	}

	/**
	 * @param mixed $state
	 * @return void
	 */
	public function restoreState($state)
	{
		$this->value = $state;
	}
}
