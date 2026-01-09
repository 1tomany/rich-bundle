<?php

namespace OneToMany\RichBundle;

use OneToMany\RichBundle\DependencyInjection\RegisterHandlersPass;
use OneToMany\RichBundle\DependencyInjection\RemoveInputsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class RichBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Remove CommandInterface, InputInterface, and ResultInterface classes
        $container->addCompilerPass(new RemoveInputsPass(), priority: RemoveInputsPass::PRIORITY);

        // Register HandlerInterface classes as handlers for the Symfony Messenger
        $container->addCompilerPass(new RegisterHandlersPass(), priority: RegisterHandlersPass::PRIORITY);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.yaml');
    }
}
