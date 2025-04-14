<?php

namespace OneToMany\RichBundle;

use OneToMany\RichBundle\DependencyInjection\Compiler\RegisterModulesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class RichBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Add the messenger.message_handler tag to all classes
        // that implement the HandlerInterface interface, and
        // ensure their handles() method uses the correct command
        $container->addCompilerPass(new RegisterModulesPass());
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.xml');
    }
}
