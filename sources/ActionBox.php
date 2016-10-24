<?php

namespace Spellu\Dsl;

class ActionPool
{
	protected $funcuit;
	protected $actions = [];

	public function __construct(Funcuit $funcuit)
	{
		$this->funcuit = $funcuit;
	}

	public function _define($name, $type, $function)
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

	public function _expression($name, array $arguments)
	{
		$action = $this->actions[$name] ?? null;

		if ($action === null) throw new DslException("expression '{$name}' not found.");

		// すべての引数をthunk化する
		$thunks = map($arguments, function ($v) { return thunk($v); });

		return new ExpressionCall($this->funcuit, $action, $thunks);
	}

	public function __call($method, $arguments)
	{
		return $this->_expression($method, $arguments);
	}

}

trait ActionBox
{
	protected $actionPool;

	public function actionPool()
	{
		if ($this->actionPool === null) {
			$this->actionPool = $this->makeActionPool();
		}

		return $this->actionPool;
	}

	protected function makeActionPool()
	{
		return new ActionPool($this);
	}

	public function define($name, $type, $function)
	{
		return $this->actionPool()->_define($name, $type, $function);
	}

	public function expression($name, ...$arguments)
	{
		return $this->actionPool()->_expression($name, $arguments);
	}
}
