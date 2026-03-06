<?php

namespace OneToMany\RichBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function array_keys;

class RemoveDtoTagsPass implements CompilerPassInterface
{
    public const string DTO_CLASS_TAG = 'onetomany.rich.dto';

    public function process(ContainerBuilder $container): void
    {
        $serviceIds = $container->findTaggedServiceIds(...[
            'name' => self::DTO_CLASS_TAG,
        ]);

        foreach (array_keys($serviceIds) as $id) {
            $container->removeDefinition($id);
        }
    }
}
