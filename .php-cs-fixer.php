<?php
declare(strict_types=1);

use Developion\CodingStandards\SetList;
use PhpCsFixer\Finder;

require 'vendor/autoload.php';

$finder = (new Finder())
	->in([
		__DIR__ . '/public',
		__DIR__ . '/src',
		__DIR__ . '/tests',
	])
	->ignoreDotFiles(false)
	->ignoreVCSIgnored(true)
	->exclude([
		'vendor',
		'node_modules',
	])
;

$config = require SetList::PHP_CS_FIXER;

return $config($finder);
