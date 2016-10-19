<?php

namespace Spellu\Dsl;

function thunk($value)
{
	if ($value instanceof Thunk) return $value;
	if ($value === null) return Thunk::fail();
	return new Thunk($value);
}

function wrap($function, ...$values)
{
	return function ($argument) use ($values) {
		$arguments = clone $values;
		array_unshift($arguments, $argument);
		return call_user_func_array($function, $arguments);
//		return call_user_func_array($function, array_merge([$argument], $values));
	};
}

function map($array, $function)
{
	$result = [];
	foreach ($array as $value) {
		$result[] = $function($value);
	}
	return $result;
}

function reduce($array, $function, $initialValue)
{
	return array_reduce($array, $function, $initialValue);
}

function dump($v)
{
	if (is_array($v)) {
		return '[' . implode(', ', map($v, function ($v) {
			return dump($v);
		})) . ']';
	}
	else {
		return (string)$v;
	}
}
