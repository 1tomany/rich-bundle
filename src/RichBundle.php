<?php

namespace OneToMany\RichBundle;

use OneToMany\RichBundle\DependencyInjection\RegisterHandlersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class RichBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Register handler objects with their commands and the
        // Symfony Messenger (if installed), and remove command,
        // input, and result objects from the compiled container
        $container->addCompilerPass(new RegisterHandlersPass());
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.yaml');
    }
}
