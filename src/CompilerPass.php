<?php

namespace OneToMany\RichBundle;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // $container->addCompilerPass();
        // var_dump($container->has(HandlerInterface::class));
        // $container->registerForAutoconfiguration(HandlerInterface::class)->addTag('rich_bundle.message_handler');
        // $container->findTaggedServiceIds('rich_bundle.message_handler');
        foreach ($container->findTaggedServiceIds('rich_bundle.message_handler') as $id => $tags) {
            $def = $container->getDefinition($id);

            $commandClass = str_replace('Handler', 'Command', $id);

            $container->setDefinition($id, $def->addTag('messenger.message_handler', ['method' => 'handle', 'handles' => $commandClass]));
            // $container->registerForAutoconfiguration($id)->;
            // var_dump($id);
            // print_r($tags);
            // ...
        }
    }
}
