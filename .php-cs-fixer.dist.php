<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfig;

$finder = new Finder();

$finder = Finder::create()
    ->in([__DIR__])
    ->exclude([
        'assets/',
        'templates/',
    ])
    ->append([__FILE__])
    ->ignoreVCSIgnored(true)
;

$config = new Config()
    ->setFinder($finder)
    ->setParallelConfig(new ParallelConfig(4))
    ->setCacheFile('var/php-cs-fixer/files.cache')
    ->setRules([
        '@Symfony' => true,
        'global_namespace_import' => [
            'import_classes' => false,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'phpdoc_align' => [
            'align' => 'left',
        ],
    ]);

return $config;
