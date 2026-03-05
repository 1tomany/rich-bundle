<?php

namespace OneToMany\RichBundle;

use OneToMany\RichBundle\DependencyInjection\RemoveDataClassesPass;
use OneToMany\RichBundle\EventListener\RequestListener;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class RichBundle extends AbstractBundle
{
    /**
     * @see Symfony\Component\Config\Definition\ConfigurableInterface
     *
     * @param DefinitionConfigurator<'array'> $definition
     */
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition
            ->rootNode()
                ->children()
                    ->arrayNode('request_listener')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->arrayNode('accept_formats')
                                ->acceptAndWrap(['string'])
                                    ->defaultValue(['json', 'xml'])
                                    ->stringPrototype()
                                ->end()
                            ->end()
                            ->arrayNode('content_type_formats')
                                ->acceptAndWrap(['string'])
                                    ->defaultValue(['form', 'json'])
                                    ->stringPrototype()
                                ->end()
                            ->end()
                            ->booleanNode('log_important_exceptions')
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
    }

    public function build(ContainerBuilder $container): void
    {
        // Remove Command, Input, and Result classes
        $container->addCompilerPass(new RemoveDataClassesPass());
    }

    /**
     * @see Symfony\Component\DependencyInjection\Extension\ConfigurableExtensionInterface
     *
     * @param array{
     *   request_listener: array{
     *     accept_formats: non-empty-list<non-empty-lowercase-string>,
     *     content_type_formats: non-empty-list<non-empty-lowercase-string>,
     *     log_important_exceptions: bool,
     *     serialized_uri_prefix: non-empty-string,
     *   },
     * } $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.yaml');

        $builder
            ->getDefinition(RequestListener::class)
            ->setArgument('$acceptFormats', $config['request_listener']['accept_formats'])
            ->setArgument('$contentTypeFormats', $config['request_listener']['content_type_formats'])
            ->setArgument('$serializedUriPrefix', $config['request_listener']['serialized_uri_prefix'])
            ->setArgument('$logImportantExceptions', $config['request_listener']['log_important_exceptions']);
    }
}
