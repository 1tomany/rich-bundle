<?php

namespace OneToMany\RichBundle\DependencyInjection\Compiler;

use OneToMany\RichBundle\Contract\CommandInterface;
use OneToMany\RichBundle\Contract\HandlerInterface;
use OneToMany\RichBundle\Contract\InputInterface;
use OneToMany\RichBundle\Contract\ResultInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Messenger\MessageBusInterface;

use function class_exists;
use function interface_exists;
use function is_subclass_of;
use function str_replace;

class RegisterModulesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
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
            if ($this->isNonHandlerRichModuleClass($class)) {
                $container->removeDefinition($id);
            }
        }
    }

    private function shouldRegisterAsMessageHandler(string $class): bool
    {
        // @see https://github.com/1tomany/rich-bundle/issues/11
        if (!class_exists($class, false)) {
            return false;
        }

        /** @disregard P1009 Undefined type */
        if (interface_exists(MessageBusInterface::class, false)) {
            return is_subclass_of($class, HandlerInterface::class);
        }

        return false;
    }

    /**
     * This attempts to generate the command class name based off
     * the handler class name. It takes the FQCN of the handler and
     * replaces the string Handler with Command. If the resulting
     * class exists and implements the CommandInterface interface,
     * it assumes that class is the command class for that handler.
     *
     * @return ?class-string<CommandInterface>
     */
    private function getHandlerCommandClass(string $class): ?string
    {
        // This is a quick-and-dirty way to generate the FQCN
        // for the command given the FQCN of the handler class
        $command = str_replace('Handler', 'Command', $class);

        // @see https://github.com/1tomany/rich-bundle/issues/11
        if (!class_exists($command, false)) {
            return null;
        }

        if (!is_subclass_of($command, CommandInterface::class)) {
            return null;
        }

        return $command;
    }

    private function isNonHandlerRichModuleClass(string $class): bool
    {
        // @see https://github.com/1tomany/rich-bundle/issues/11
        if (!class_exists($class, false)) {
            return false;
        }

        return
            is_subclass_of($class, CommandInterface::class)
            || is_subclass_of($class, InputInterface::class)
            || is_subclass_of($class, ResultInterface::class)
        ;
    }
}
