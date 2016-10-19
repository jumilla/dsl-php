<?php

require __DIR__.'/../vendor/autoload.php';

use Spellu\Dsl\State;
use Spellu\Dsl\Thunk;
use function Spellu\Dsl\toValue;



$state = new State();

$state->define('test1', '', function (State $self, $value) {
	return toValue($value) + 1;
});

$state->define('add', '', function (State $self, $value) {
	$self->value += toValue($value);
	return Thunk::void();
});

$state->define('test2', '', function (State $self, $value) {
	return $self->op->bind(
		$self->add($value),
		$self->test1($self->get())
	);
});

$state->define('test3', '', function (State $self, $value) {
	return $self->add($value)->add($value)->test1($self->get());
});


echo $state->run($state->test2(3), 134), PHP_EOL;
echo $state->run($state->test3(5), 134), PHP_EOL;
