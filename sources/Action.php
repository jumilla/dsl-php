<?php

namespace Spellu\Dsl;

class Action
{
	protected $name;
	protected $type;
	protected $function;

	public function __construct($name, $type, $function)
	{
		$this->name = $name;
		$this->type = $type;
		$this->function = $function;
	}

	public function argCount()
	{
		$ref = new ReflectionFunction($this->function);

		return $ref->getNumberOfParameters() - 1;
	}

	public function __invoke(...$args)
	{
		return call_user_func_array($this->function, $args);
	}
}

trait ActionPool
{
	protected $actions = [];

	public function define($name, $type, $function)
	{
		$ref = new \ReflectionFunction($function);
		$parameters = $ref->getParameters();

		if (! $parameters[0]->hasType()) {
			throw new DslException('action closure must has funcuit parameter.');
		}
//		var_dump((string)$parameters[0]->getType());
		// TODO: 第一引数の型がSpellu\Dsl\Funcuitを実装していることを確認する

		$this->actions[$name] = new Action($name, $type, $function);
	}

	public function expression($name, ...$arguments)
	{
		return $this->expressionA($name, $arguments);
	}

	public function expressionA($name, $arguments)
	{
		$action = $this->actions[$name] ?? null;

		if ($action === null) throw new DslException("expression '{$name}' not found.");

		// すべての引数をthunk化する
		$thunks = map($arguments, function ($v) { return thunk($v); });

		return new ExpressionCall($this, $action, $thunks);
	}
}
