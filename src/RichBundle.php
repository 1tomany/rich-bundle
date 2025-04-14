<?php

namespace OneToMany\RichBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class RichBundle extends AbstractBundle
{

    public function build(ContainerBuilder $container): void
    {
        $container->registerForAutoConfiguration(\OneToMany\RichBundle\Contract\HandlerInterface::class)->addTag('messenger.message_handler', ['method' => 'handle']);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.xml');
    }
}
