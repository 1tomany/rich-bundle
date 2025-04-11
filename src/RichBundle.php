<?php

namespace OneToMany\RichBundle;

use OneToMany\RichBundle\Contract\HandlerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class RichBundle extends AbstractBundle
{
    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.xml');

        // $container->instanceof(HandlerInterface::class)->tag('messenger.message_handler');

        // $builder
        //             ->registerForAutoconfiguration(HandlerInterface::class)
        //             ->addTag('messenger.message_handler', ['method' => 'handle'])
        //         ;

        // $def = $builder->findDefinition(HandlerInterface::class);
        // var_dump(gettype($def));
        // var_dump($builder->has(HandlerInterface::class));
        // $builder->registerAttributeForAutoconfiguration();
    }
}
