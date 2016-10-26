#!/usr/bin/env php
<?php

require __DIR__.'/../vendor/autoload.php';

use Spellu\Dsl\Example\Calcurator;
use function Spellu\Dsl\dump;



$calcurator = new Calcurator();

function calc($string)
{
	global $calcurator;

	$results = [];

	$results[] = $calcurator->runCalcurator($string, true)->either(
		function ($result) {
			return dump($result);
		},
		function ($result) {
			return 'Error: ' . $result;
		}
	);

	$results[] = $calcurator->runCalcurator($string, false)->either(
		function ($result) {
			return dump($result);
		},
		function ($result) {
			return 'Error: ' . $result;
		}
	);

	return dump($results);
}

/*
echo '[1] ', calc('1+2+3'), PHP_EOL;
echo '[2] ', calc('1*2+3'), PHP_EOL;
echo '[3] ', calc('1+2*3'), PHP_EOL;
echo '[4] ', calc('(1+2)*3'), PHP_EOL;
echo '[5] ', calc('1a+2'), PHP_EOL;
*/
echo '[6] ', calc('(1+9a)*3'), PHP_EOL;
