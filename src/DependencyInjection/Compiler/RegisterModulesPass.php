<?php

namespace OneToMany\RichBundle\DependencyInjection\Compiler;

use OneToMany\RichBundle\Contract\CommandInterface;
use OneToMany\RichBundle\Contract\HandlerInterface;
use OneToMany\RichBundle\Contract\InputInterface;
use OneToMany\RichBundle\Contract\ResultInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Messenger\MessageBusInterface;

class RegisterModulesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();

            if (!$class) {
                continue;
            }

            // Tag handlers to be used by Symfony Messenger
            if ($this->shouldRegisterAsMessageHandler($class)) {
                $command = $this->getHandlerCommandClass($class);

                if (!$command) {
                    continue;
                }

                if (!$definition->hasTag('messenger.message_handler')) {
                    $definition->addTag('messenger.message_handler', [
                        'method' => 'handle', 'handles' => $command,
                    ]);

                    $container->setDefinition($class, $definition);
                }
            }

            // Remove input, command, and result classes from the
            // container because they'll be instantiated elsewhere
            if ($this->isNonServiceClass($class)) {
                $container->removeDefinition($id);
            }
        }
    }

    private function shouldRegisterAsMessageHandler(string $class): bool
    {
        if (!\class_exists($class)) {
            return false;
        }

        if (\interface_exists(MessageBusInterface::class)) {
            return \is_subclass_of($class, HandlerInterface::class);
        }

        return false;
    }

    /**
     * @return ?class-string<CommandInterface>
     */
    private function getHandlerCommandClass(string $class): ?string
    {
        // Put the command class in the right namespace
        $command = \str_replace('\\Handler\\', '\\Command\\', $class);

        // Name the command class with the correct suffix
        $command = \str_replace('Handler', 'Command', $command);

        if (!\class_exists($command)) {
            return null;
        }

        if (!\is_subclass_of($command, CommandInterface::class)) {
            return null;
        }

        return $command;
    }

    private function isNonServiceClass(string $class): bool
    {
        if (!\class_exists($class)) {
            return false;
        }

        return (
            \is_subclass_of($class, CommandInterface::class) ||
            \is_subclass_of($class, InputInterface::class) ||
            \is_subclass_of($class, ResultInterface::class)
        );
    }
}
