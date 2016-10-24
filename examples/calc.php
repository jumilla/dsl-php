#!/usr/bin/env php
<?php

require __DIR__.'/../vendor/autoload.php';

use Spellu\Dsl\Example\Calcurator;
use function Spellu\Dsl\dump;



$calcurator = new Calcurator();

echo '[1] ', dump($calcurator->runTwoMode('1+2+3')), PHP_EOL;
echo '[2] ', dump($calcurator->runTwoMode('1*2+3')), PHP_EOL;
echo '[3] ', dump($calcurator->runTwoMode('1+2*3')), PHP_EOL;
echo '[4] ', dump($calcurator->runTwoMode('(1+2)*3')), PHP_EOL;
