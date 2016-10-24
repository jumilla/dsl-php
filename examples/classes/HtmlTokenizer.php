<?php

namespace Spellu\Dsl\Example;

use Spellu\Dsl\Restorable;

class HtmlTokenizer implements Restorable
{
	public function __construct($string)
	{
		$this->string = $string;
		$this->offset = 0;
		$this->end = strlen($string);
	}

	
}
