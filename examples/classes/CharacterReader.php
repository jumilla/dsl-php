<?php

namespace Spellu\Dsl\Example;

use Spellu\Dsl\Restorable;

class CharacterReader implements Restorable
{
	protected $string;
	protected $offset;
	protected $end;
	protected $line;
	protected $column;

	public function __construct($string)
	{
		$this->string = $string;
		$this->offset = 0;
		$this->end = strlen($string);
		$this->line = 1;
		$this->column = 1;
	}

	public function peek()
	{
		if ($this->offset >= $this->end)
			return null;

		return $this->string[$this->offset];
	}

	public function read()
	{
		if ($this->offset >= $this->end)
			return null;

		$char = $this->string[$this->offset];

		$this->offset += 1;
		$this->column += 1;

		return (object)[
			'char' => $char,
			'line' => $this->line,
			'column' => $this->column,
		];
	}

	public function saveState()
	{
		return [
			'offset' => $this->offset,
			'line' => $this->line,
			'column' => $this->column,
		];
	}

	public function restoreState($state)
	{
		$this->offset = $state['offset'];
		$this->line = $state['line'];
		$this->column = $state['column'];
	}
}
