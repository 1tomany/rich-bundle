<?php

namespace OneToMany\RichBundle;

use OneToMany\RichBundle\DependencyInjection\RegisterHandlersPass;
use OneToMany\RichBundle\DependencyInjection\RemoveInputsPass;
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
        $definition->import('../config/config.php');
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Remove CommandInterface, InputInterface, and ResultInterface classes
        $container->addCompilerPass(new RemoveInputsPass(), priority: RemoveInputsPass::PRIORITY);

        // Register HandlerInterface classes as handlers for the Symfony Messenger
        $container->addCompilerPass(new RegisterHandlersPass(), priority: RegisterHandlersPass::PRIORITY);
    }

    /**
     * @see Symfony\Component\DependencyInjection\Extension\ConfigurableExtensionInterface
     *
     * @param array{
     *   request_listener: array{
     *     accept_formats: non-empty-list<non-empty-lowercase-string>,
     *     content_type_formats: non-empty-list<non-empty-lowercase-string>,
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
            ->setArgument('$serializedApiPrefix', $config['request_listener']['serialized_uri_prefix']);
    }
}
