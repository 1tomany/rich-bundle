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

        // This pass adds the 'messenger.message_handler' tag to all classes
        // that implement the HandlerInterface interface, and sets the command
        // class each handler's handle() method should use. The priority is set
        // to a value higher than the priority of the compiler pass provided by
        // the Symfony Messenger component to ensure this pass is processed first.
        $container->addCompilerPass(pass: new RegisterModulesPass(), priority: 1);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.xml');
    }
}
