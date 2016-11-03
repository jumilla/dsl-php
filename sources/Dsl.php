<?php

namespace Spellu\Dsl;

class DslException extends \Exception
{
}

class Action
{
	protected $name;
	protected $type;
	protected $function;

	public function __construct($name, $type, $function)
	{
		$this->name = $name;
		$this->type = $type;
		$this->function = $function;
	}

	/**
	 * @return int
	 */
	public function argumentCount()
	{
		$ref = new \ReflectionFunction($this->function);

		return $ref->getNumberOfParameters() - 1;
	}

	public function name()
	{
		return $this->name;
	}

	public function type()
	{
		return $this->type;
	}

	public function __invoke(...$args)
	{
		return call_user_func_array($this->function, $args);
	}
}

interface Failable
{
	/**
	 * @return bool
	 */
	public function isFailure();
}

interface Evaluable
{
	/**
	 * @param string $name
	 * @param array $args
	 * @return Spellu\Dsl\Evaluable
	 */
	public function bind($name, $arguments);

	/**
	 * @param callable $reducer
	 * @return Spellu\Dsl\Evaluable
	 */
	public function reduce(callable $reducer);

	/**
	 * @return Spellu\Dsl\Thunk
	 */
	public function __invoke();
}

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
