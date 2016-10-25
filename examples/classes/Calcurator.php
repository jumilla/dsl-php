<?php

namespace Spellu\Dsl\Example;

use Spellu\Dsl\Funcuit;
use Spellu\Dsl\Either;
use Spellu\Dsl\Success;
use Spellu\Dsl\Failure;
use function Spellu\Dsl\thunk;

/**
 * EBNF
 * expr = term { '*' term | '/' term }
 * term = factor { '+' factor | '-' factor }
 * factor = number | '(' expr ')'
 * number = { '0' | '1' | '2' | '3' | '4' | '5' | '6' | '7' | '8' | '9' }+
 */
class Calcurator extends Funcuit
{
	protected $stream;

	public $direct;

	public function __construct()
	{
		$this->setupActions();
	}

//--- for external --//

	public function runTwoMode($string)
	{
		return [
			'direct' => $this->runCalcurator($string, true),
			'late' => $this->runCalcurator($string, false)
		];
	}

	public function runCalcurator($string, $direct)
	{
		$this->stream = new CharacterReader($string);
		$this->direct = $direct;

		$result = thunk($this->ac->expr())->evaluate();

		if ($result instanceof Either) {
			return $result;
		}

		return new Success($direct ? $result : $this->calcLater($result));
	}

//-- for internal --//

	protected function setupActions()
	{
		$this->define('expr', '', function (Calcurator $self) {
			return $self->op->concat(
				$self->ac->term(),
				$self->op->many(
					$self->op->or(
						$self->ac->char('+')->term(),
						$self->ac->char('-')->term()
					)
				)
			)->reduceExpression();
		});

		$this->define('term', '', function (Calcurator $self) {
			return $self->op->concat(
				$self->ac->factor(),
				$self->op->many(
					$self->op->or(
						$self->ac->char('*')->factor(),
						$self->ac->char('/')->factor()
					)
				)
			)->reduceExpression();
		});

		$this->define('factor', '', function (Calcurator $self) {
			return $self->op->or(
				$self->ac->number(),
				$self->ac->char('(')->expr()->char(')')->reduce(function (array $result) {
					assert(count($result) == 3);
					return $result[1];
				})
			);
		});

		$this->define('number', '', function (Calcurator $self) {
			$result = '';
			if ($char = $self->getDigit()) {
				$result .= $char->char;
			}
			else {
				return null;
			}

			while (true) {
				$char = $self->getDigit();
				if ($char === null) {
					return new Failure(new CalcuratorException($self->stream->read(), 'not a number'));
				}
				$result .= $char->char;
			}
			$a = 9a;
			return $result;
		});

		$this->define('char', '', function (Calcurator $self, $char) {
			$char = $char->evaluate();
			if ($result = $self->getCharIf($char)) {
				return $result;
			}
			return new Failure(new CalcuratorException($self->stream->read(), "Expected '{$char}'"));
		});
	}

	public function getChar()
	{
		return $this->stream->read();
	}

	public function getCharIf($char)
	{
		if ($this->stream->peek() == $char) {
			return $this->stream->read();
		}
		else
			return null;
	}

	public function getSpace()
	{
		if (preg_match('/[ \\t\\r\\n]/', $this->stream->peek())) {
			return $this->stream->read();
		}
		else
			return null;
	}

	public function getDigit()
	{
		if (preg_match('/[0-9]/', $this->stream->peek())) {
			return $this->stream->read();
		}
		else
			return null;
	}

	public function reduceExpression($expression)
	{
		return $expression->reduce(function (array $result) {
			assert(count($result) == 2);
			if ($this->direct) {
				return $this->calcDirect($result);
			}
			else {
				if (empty($result[1])) return $result[0];
				return array_merge([$result[0]], $result[1]);
			}
		});
	}

	public function calcDirect($expr)
	{
		$left = (int)$expr[0];
		foreach ($expr[1] as $postfix) {
			list($sign, $right) = $postfix;
			switch ($sign->char) {
				case '+':
					 $left += (int)$right;
					 break;
				case '-':
					 $left -= (int)$right;
					 break;
				case '*':
					 $left *= (int)$right;
					 break;
				case '/':
					 $left /= (int)$right;
					 break;
				default:

			}
		}
		return $left;

	}

	public function calcLater($expr)
	{
		return $this->calc($expr);
	}

	public function calc($expr)
	{
		if (is_numeric($expr)) {
			return (int)$expr;
		}

		assert(is_array($expr));

		$left = $this->calc($expr[0]);
		foreach (array_slice($expr, 1) as $postfix) {
			list($sign, $right) = $postfix;
			switch ($sign->char) {
				case '+':
					 $left += $this->calc($right);
					 break;
				case '-':
					 $left -= $this->calc($right);
					 break;
				case '*':
					 $left *= $this->calc($right);
					 break;
				case '/':
					 $left /= $this->calc($right);
					 break;
			}
		}
		return $left;

	}

	public function saveState()
	{
		return $this->stream->saveState();
	}

	public function restoreState($state)
	{
		$this->stream->restorestate($state);
	}
}
