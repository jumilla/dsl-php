<?php

require __DIR__.'/../vendor/autoload.php';

use Spellu\Dsl\Funcuit;
use Spellu\Dsl\ActionPool;
use Spellu\Dsl\Control;
use function Spellu\Dsl\thunk;
use function Spellu\Dsl\dump;



class CharacterStream
{
	public function __construct($string)
	{
		$this->string = $string;
		$this->offset = 0;
		$this->end = strlen($string);
	}

	public function peek()
	{
		if ($this->offset >= $this->end)
			return null;

		return $this->string[$this->offset];
	}

	public function get()
	{
		if ($this->offset >= $this->end)
			return null;

		return $this->string[$this->offset++];
	}

	public function save()
	{
		return [
			'offset' => $this->offset,
		];
	}

	public function restore($state)
	{
		$this->offset = $state['offset'];
	}
}

/**
 * EBNF
 * expr = term { '*' term | '/' term }
 * term = factor { '+' factor | '-' factor }
 * factor = number | '(' expr ')'
 * number = { '0' | '1' | '2' | '3' | '4' | '5' | '6' | '7' | '8' | '9' }+
 */
class Calcurator extends Funcuit
{
	use ActionPool;

	protected $stream;

	public $direct;

	public function __construct()
	{
		$this->op = new Control($this);
	}

	public function __call($method, $args)
	{
		return $this->expressionA($method, $args);
	}

	public function runTwoMode($string)
	{
		return [
			'direct' => $this->runCalcurator($string, true),
			'late' => $this->runCalcurator($string, false)
		];
	}

	public function runCalcurator($string, $direct)
	{
		$this->stream = new CharacterStream($string);
		$this->direct = $direct;

		$result = thunk($this->expr())->evaluate();

		return $direct ? $result : $this->calcLater($result);
	}

	public function getChar()
	{
		return $this->stream->get();
	}

	public function getCharIf($char)
	{
		$next = $this->stream->peek();
		if ($next == $char) {
			$this->stream->get();
			return $next;
		}
		else
			return null;
	}

	public function getDigit()
	{
		$next = $this->stream->peek();
		if (preg_match('/[0-9]/', $next)) {
			$this->stream->get();
			return $next;
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
			switch ($sign) {
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
			switch ($sign) {
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

	public function __runnable_save()
	{
		return $this->stream->save();
	}

	public function __runnable_restore($state)
	{
		$this->stream->restore($state);
	}
}

$calcurator = new Calcurator();

$calcurator->define('expr', '', function (Calcurator $self) {
	return $self->op->concat(
		$self->term(),
		$self->op->many(
			$self->op->or(
				$self->char('+')->term(),
				$self->char('-')->term()
			)
		)
	)->reduceExpression();
});

$calcurator->define('term', '', function (Calcurator $self) {
	return $self->op->concat(
		$self->factor(),
		$self->op->many(
			$self->op->or(
				$self->char('*')->factor(),
				$self->char('/')->factor()
			)
		)
	)->reduceExpression();
});

$calcurator->define('factor', '', function (Calcurator $self) {
	return $self->op->or(
		$self->number(),
		$self->char('(')->expr()->char(')')->reduce(function (array $result) {
			assert(count($result) == 3);
			return $result[1];
		})
	);
});

$calcurator->define('number', '', function (Calcurator $self) {
	$result = '';
	while (true) {
		$char = $self->getDigit();
		if ($char === null) break;
		$result .= $char;
	}
	return $result ?: null;
});

$calcurator->define('char', '', function (Calcurator $self, $char) {
	if ($char = $self->getCharIf($char->evaluate())) {
		return $char;
	}
	return null;
});

echo '[1] ', dump($calcurator->runTwoMode('1+2+3')), PHP_EOL;
echo '[2] ', dump($calcurator->runTwoMode('1*2+3')), PHP_EOL;
echo '[3] ', dump($calcurator->runTwoMode('1+2*3')), PHP_EOL;
echo '[4] ', dump($calcurator->runTwoMode('(1+2)*3')), PHP_EOL;
