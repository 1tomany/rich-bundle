<?php

$finder = new \PhpCsFixer\Finder()->exclude('config')->in(__DIR__);

return new \PhpCsFixer\Config()->setFinder($finder)->setRules([
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
