<?php

namespace Spellu\Dsl\Example;

use Spellu\Dsl\Funcuit;
use Spellu\Dsl\Thunk;
use function Spellu\Dsl\thunk;

class HtmlParser extends Funcuit
{
	protected $tokenizer;

	public function __construct()
	{
		$this->setupActions();
	}

	public function runParse($string)
	{
		$this->stream = new CharacterReader($string);

		return thunk($this->ac->root())->evaluate();
	}

	protected function setupActions()
	{
		$this->define('root', 'void -> void', function (HtmlParser $self) {
			return Thunk::fail();
		});
	}
}
