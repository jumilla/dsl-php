<?php

namespace Spellu\Dsl\Example;

use Spellu\Dsl\Restorable;

class CharacterReader implements Restorable
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

	public function read()
	{
		if ($this->offset >= $this->end)
			return null;

		return $this->string[$this->offset++];
	}

	public function saveState()
	{
		return [
			'offset' => $this->offset,
		];
	}

	public function restoreState($state)
	{
		$this->offset = $state['offset'];
	}
}
