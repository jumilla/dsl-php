<?php

namespace Spellu\Dsl\Example;

use Spellu\Dsl\Funcuit;
use Spellu\Dsl\ActionPool;
use Spellu\Dsl\Control;

class HtmlParser extends Funcuit
{
	use ActionPool;

	protected $tokenizer;

	public function __construct()
	{
		$this->op = new Control($this);

		$this->setupActions();
	}

	protected function setupActions()
	{
	}

	public function runParse($string)
	{
	}
}
