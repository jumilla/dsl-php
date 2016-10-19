<?php

namespace Spellu\Dsl;

function thunk($value)
{
	if ($value instanceof Thunk) return $value;
	if ($value === null) return Thunk::nothing();
	return new Thunk($value);
}

/**
 * thunkを評価し、値に還元する。
 */
function toValue(Thunk &$thunk)
{
	while ($thunk->isExpression()) {
		$thunk->evaluate();
	}
	return $thunk->value();
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
