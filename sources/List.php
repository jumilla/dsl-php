<?php

namespace Spellu\Dsl;

// data [] a  =  []] | a : [a] deriving (Eq, Ord, Read, Show)

interface List extends Failable
{
}

class List implements Listable
{
	public function __construct($type, $values)
	{
		$this->values = $values;
	}

	public function isFailure()
	{
		return false;
	}
}

class Nil implements Listable
{
	public function __construct()
	{
	}

	public function isFailure()
	{
		return true;
	}
}
