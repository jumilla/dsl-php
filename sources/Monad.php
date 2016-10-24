<?php

namespace Spellu\Dsl;

interface Restorable
{
	/**
	 * @return mixed
	 */
	public function saveState();

	/**
	 * @param mixed $state
	 * @return void
	 */
	public function restoreState($state);
}

interface Runnable
{
	/**
	 * @return mixed
	 */
	public function __runnable_save();

	/**
	 * @param mixed $state
	 * @return void
	 */
	public function __runnable_restore($state);
}

class Funcuit implements Runnable
{
	/**
	 * @return mixed
	 */
	public function __runnable_save()
	{
		return null;
	}

	/**
	 * @param mixed $state
	 * @return void
	 */
	public function __runnable_restore($state)
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

class DslException extends \Exception
{
}
