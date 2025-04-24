<?php

$finder = new PhpCsFixer\Finder()
    ->exclude([
        './config/',
        './var/',
    ])
    ->in([
        './src/',
        './tests/',
    ]);

return new PhpCsFixer\Config()
    ->setFinder($finder)
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
        'phpdoc_to_comment' => [
            'ignored_tags' => [
                'disregard',
            ],
        ],
    ]);
