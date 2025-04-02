<?php

$finder = new \PhpCsFixer\Finder()->exclude('config')->in(__DIR__);

return new \PhpCsFixer\Config()->setFinder($finder)->setRules([
    '@Symfony' => true,
    'phpdoc_align' => [
        'align' => 'left',
    ],
]);
