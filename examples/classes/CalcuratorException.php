<?php

namespace Spellu\Dsl\Example;

use Spellu\Dsl\DslException;

class CalcuratorException extends DslException
{
	public function __construct($char, $message)
	{
		parent::__construct($message);
		$this->char = $char;
	}

	public function __toString()
	{
		if ($this->char)
			return "({$this->char->line}:{$this->char->column}) {$this->getMessage()}";
		else
			return "(EOS) {$this->getMessage()}";
	}
}
