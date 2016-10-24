#!/usr/bin/env php
<?php

require __DIR__.'/../vendor/autoload.php';

use Spellu\Dsl\Example\HtmlParser;
use function Spellu\Dsl\dump;



$parser = new HtmlParser();

echo '[1] ', dump($parser->runParse('1+2+3')), PHP_EOL;
