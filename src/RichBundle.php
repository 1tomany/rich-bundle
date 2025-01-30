<?php

namespace OneToMany\RichBundle;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class RichBundle extends AbstractBundle
{

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.xml');
    }
    
}
