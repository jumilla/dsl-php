<?php

namespace Spellu\Dsl;

interface Maybe extends Failable
{
}

class Just implements Maybe
{
	public function __construct($value)
	{
		$this->value = $value;
	}

	public function isFailure()
	{
		return false;
	}
}

class Nothing implements Maybe
{
	public function __construct()
	{
	}

	public function isFailure()
	{
		return true;
	}
}
