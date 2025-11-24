<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfig;

$finder = new Finder();

$finder->in([
    './',
    './bin/',
    './src/',
    './tests/',
]);

$finder->append([
    './bin/rich',
]);

$finder->exclude([
    'config',
    'vendor',
]);

$config = new Config()->setParallelConfig(...[
    'config' => new ParallelConfig(4),
]);

$config->setFinder($finder);
$config->setRules([
    '@Symfony' => true,
    'global_namespace_import' => [
        'import_classes' => false,
        'import_constants' => true,
        'import_functions' => true,
    ],
    'phpdoc_align' => [
        'align' => 'left',
    ],
    'phpdoc_to_comment' => [
        'ignored_tags' => [
            'disregard',
        ],
    ],
]);

return $config;
