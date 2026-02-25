<?php

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

/**
 * @param DefinitionConfigurator<'array'> $configurator
 */
$configurator = static function (DefinitionConfigurator $configurator): void {
    $configurator
        ->rootNode()
            ->children()
                ->arrayNode('request_listener')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('accept_formats')
                            ->acceptAndWrap(['string'])
                            ->defaultValue(['json', 'xml'])
                            ->stringPrototype()->end()
                        ->end()
                        ->arrayNode('content_type_formats')
                            ->acceptAndWrap(['string'])
                            ->defaultValue(['form', 'json'])
                            ->stringPrototype()->end()
                        ->end()
                        ->booleanNode('log_critical_exceptions')
                            ->defaultTrue()
                        ->end()
                        ->stringNode('serialized_uri_prefix')
                            ->cannotBeEmpty()
                            ->defaultValue('/api')
                            ->validate()
                                ->ifFalse(static fn(string $v): bool => str_starts_with($v, '/'))
                                ->thenInvalid('Prefix must start with a forward slash.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
};

return $configurator;
