<?php

namespace OneToMany\RichBundle\DependencyInjection;

use OneToMany\RichBundle\Contract\Action\CommandInterface;
use OneToMany\RichBundle\Contract\Action\InputInterface;
use OneToMany\RichBundle\Contract\Action\ResultInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use function class_exists;
use function is_subclass_of;

class RemoveInputsPass implements CompilerPassInterface
{
    public const int PRIORITY = 0;

    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $id => $definition) {
            if ($class = $definition->getClass()) {
                if ($this->isNonServiceClass($class)) {
                    if ($container->hasDefinition($class)) {
                        $container->removeDefinition($class); // @see https://github.com/1tomany/rich-bundle/issues/81
                    }
                }
            }
        }
    }

    private function isNonServiceClass(string $class): bool
    {
        // @see https://github.com/1tomany/rich-bundle/issues/11
        return class_exists($class, false) && (is_subclass_of($class, CommandInterface::class) || is_subclass_of($class, InputInterface::class) || is_subclass_of($class, ResultInterface::class));
    }
}
