<?php

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

/**
 * @param DefinitionConfigurator<'array'> $configurator
 */
$configurator = static function (DefinitionConfigurator $configurator): void {
    $configurator
        ->rootNode()
            ->children()
                ->arrayNode('request_listener')
                    ->children()
                        ->arrayNode('accept_formats')
                            ->defaultValue(['json', 'xml'])
                            ->addDefaultsIfNotSet()
                        ->end()
                        ->arrayNode('content_type_formats')
                            ->defaultValue(['form', 'json'])
                            ->addDefaultsIfNotSet()
                        ->end()
                        ->stringNode('serialized_uri_prefix')
                            ->cannotBeEmpty()
                            ->defaultValue('/api')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
};

return $configurator;
