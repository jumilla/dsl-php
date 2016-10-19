<?php

namespace Spellu\Dsl;

interface Funcuit
{
	public function __funcuit_save();
	public function __funcuit_restore($state);
}

class DslException extends \Exception
{
}
