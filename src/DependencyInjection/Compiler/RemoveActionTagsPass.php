<?php

namespace OneToMany\RichBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function array_keys;

class RemoveActionTagsPass implements CompilerPassInterface
{
    public const string ACTION_CLASS_TAG = 'onetomany.rich.action';

    public function process(ContainerBuilder $container): void
    {
        $serviceIds = $container->findTaggedServiceIds(...[
            'name' => self::ACTION_CLASS_TAG,
        ]);

        foreach (array_keys($serviceIds) as $id) {
            $container->removeDefinition($id);
        }
    }
}
