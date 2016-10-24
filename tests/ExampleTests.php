<?php

use Spellu\Dsl\Testing\TestCase;
use Spellu\Dsl\State;

class ExampleTests extends TestCase
{
	/**
	 * @test
	 */
	public function appendValue()
	{
		$funcuit = new State();
		$funcuit->define('append', 'int -> int', function (State $self, $value) {
			$self->value += $value->evaluate();
			return $self->value;
		});

		$this->assertSame(36, $funcuit->runState($funcuit->append(24), 12));
		$this->assertSame(20, $funcuit->runState($funcuit->append(24), -4));
	}

	/**
	 * @test
	 */
	public function operationAnd()
	{
		$funcuit = new State();
		$funcuit->define('append', 'int -> int', function (State $self, $value) {
			$self->value += $value->evaluate();
			return $self->value;
		});
		$funcuit->define('foo', 'int -> int', function (State $self, $value) {
			$value = $value->evaluate();
			return $self->op->and(
				$self->append($value),
				$self->append($value * 2)
			);
		});

		$this->assertSame(84, $funcuit->runState($funcuit->foo(24), 12));
		$this->assertSame(0, $funcuit->runState($funcuit->foo(-4), 12));
	}

	/**
	 * @test
	 */
	public function methodChain()
	{
		$funcuit = new State();
		$funcuit->define('append', 'int -> int', function (State $self, $value) {
			$self->value += $value->evaluate();
			return $self->value;
		});
		$funcuit->define('foo', 'int -> int', function (State $self, $value) {
			return $self->append($value)->append($value);
		});

		$this->assertSame([36, 60], $funcuit->runState($funcuit->foo(24), 12));
		$this->assertSame([5, -2], $funcuit->runState($funcuit->foo(-7), 12));
	}
}
