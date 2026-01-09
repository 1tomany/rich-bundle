<?php

namespace OneToMany\RichBundle;

use OneToMany\RichBundle\DependencyInjection\MessengerPass;
use OneToMany\RichBundle\DependencyInjection\RemovePass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
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
        $container->addCompilerPass(new MessengerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, MessengerPass::PRIORITY);
        $container->addCompilerPass(new RemovePass(), PassConfig::TYPE_REMOVE, RemovePass::PRIORITY);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.yaml');
    }
}
