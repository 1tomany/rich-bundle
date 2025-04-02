<?php

$finder = new \PhpCsFixer\Finder()->exclude('config')->in(__DIR__);

return new \PhpCsFixer\Config()->setFinder($finder)->setRules([
    '@Symfony' => true,
    'ordered_types' => [
        'null_adjustment' => 'always_first',
    ],
    'phpdoc_align' => [
        'align' => 'left',
    ],
]);
