<?php

require __DIR__.'/../vendor/autoload.php';

use Spellu\Dsl\State;
use Spellu\Dsl\Thunk;
use function Spellu\Dsl\toValue;



$state = new State();

$state->define('test1', '', function (State $self, $value) {
	return $value->evaluate() + 1;
});

$state->define('add', '', function (State $self, $value) {
	$self->value += $value->evaluate();
	return Thunk::void();
});

$state->define('test2', '', function (State $self, $value) {
	return $self->op->and(
		$self->add($value),
		$self->test1($self->get())
	);
});

$state->define('test3', '', function (State $self, $value) {
	return $self->add($value)->add($value)->test1($self->get())->reduceR();
});


echo '[1] ', $state->runState($state->test1(3), 134), PHP_EOL;
echo '[2] ', $state->runState($state->test2(3), 134), PHP_EOL;
echo '[3] ', $state->runState($state->test3(5), 134), PHP_EOL;
