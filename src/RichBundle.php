<?php

namespace OneToMany\RichBundle;

use OneToMany\RichBundle\Contract\Input\InputParserInterface;
use OneToMany\RichBundle\DependencyInjection\RemoveDataClassesPass;
use OneToMany\RichBundle\EventListener\RequestListener;
use OneToMany\RichBundle\Form\InputDataMapper;
use OneToMany\RichBundle\Input\InputParser;
use OneToMany\RichBundle\Serializer\HttpErrorNormalizer;
use OneToMany\RichBundle\ValueResolver\InputValueResolver;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class RichBundle extends AbstractBundle
{
    protected string $extensionAlias = 'onetomany_rich';

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
                                    ->ifFalse(static fn (string $v): bool => str_starts_with($v, '/'))
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
        // $container->addCompilerPass(new RemoveDataClassesPass());
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
        $container
            ->services()
                // Input Parsers
                ->set(InputParser::class)
                    ->arg('$containerBag', service('parameter_bag'))
                    ->arg('$serializer', service('serializer'))
                    ->arg('$validator', service('validator'))
                    ->arg('$tokenStorage', service('security.token_storage')->nullOnInvalid())
                    ->alias(InputParserInterface::class, service(InputParser::class))

                // Event Subscribers
                ->set(RequestListener::class)
                    ->tag('kernel.event_subscriber')
                    ->arg('$logger', service('logger'))
                    ->arg('$serializer', service('serializer'))
                    ->arg('$acceptFormats', $config['request_listener']['accept_formats'])
                    ->arg('$contentTypeFormats', $config['request_listener']['content_type_formats'])
                    ->arg('$serializedUriPrefix', $config['request_listener']['serialized_uri_prefix'])
                    ->arg('$logImportantExceptions', $config['request_listener']['log_important_exceptions'])

                // Forms
                ->set(InputDataMapper::class)
                    ->arg('$requestStack', service('request_stack'))
                    ->arg('$inputParser', service(InputParser::class))

                // Normalizers
                ->set(HttpErrorNormalizer::class)
                    ->tag('serializer.normalizer')
                    ->arg('$debug', param('kernel.debug'))

                // Value Resolvers
                ->set(InputValueResolver::class)
                    ->tag('controller.argument_value_resolver')
                    ->arg('$inputParser', service(InputParser::class))
                    ->arg('$validator', service('validator'))
        ;

        // $container->import('../config/services.yaml');

        // $builder
        //     ->getDefinition(RequestListener::class)
        //     ->setArgument('$acceptFormats', $config['request_listener']['accept_formats'])
        //     ->setArgument('$contentTypeFormats', $config['request_listener']['content_type_formats'])
        //     ->setArgument('$serializedUriPrefix', $config['request_listener']['serialized_uri_prefix'])
        //     ->setArgument('$logImportantExceptions', $config['request_listener']['log_important_exceptions']);
    }
}
