<?php

namespace Spellu\Dsl;

interface Either extends Failable
{
    public function success(callable $success);
    public function failure(callable $failure);
    public function either(callable $success, callable $failure);
    public function value();
}

class Success implements Either
{
	protected $value;

	public function __construct($value)
	{
		$this->value = $value;
	}

	public function isFailure()
	{
		return false;
	}

	public function success(callable $callback)
	{
		$callback();
		return $this;
	}

	public function failure(callable $callback)
	{
		return $this;
	}

    public function either(callable $success, callable $failure)
    {
    	return $success($this->value);
    }

    public function value()
    {
    	return $this->value;
    }
}

class Failure implements Either
{
	protected $value;

	public function __construct($value)
	{
		$this->value = $value;
	}

	public function isFailure()
	{
		return true;
	}

	public function success(callable $callback)
	{
		return $this;
	}

	public function failure(callable $callback)
	{
		$callback();
		return $this;
	}

    public function either(callable $success, callable $failure)
    {
    	return $failure($this->value);
    }

    public function value()
    {
    	return $this->value;
    }
}
