<?php

namespace Spellu\Dsl;

class Funcuit implements Restorable
{
	use ControlBox;
	use ActionBox;

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		switch ($name) {
			case 'op':
				return $this->control();
			case 'ac':
				return $this->actionPool();
			default:
				return null;
		}
	}

	/**
	 * @return mixed
	 */
	public function saveState()
	{
		return null;
	}

	/**
	 * @param mixed $state
	 * @return void
	 */
	public function restoreState($state)
	{
	}

	/**
	 * @param Thunk $thunk
	 * @return mixed
	 */
	public function evaluate(Thunk $thunk)
	{
		return $thunk->evaluate();
	}
}
